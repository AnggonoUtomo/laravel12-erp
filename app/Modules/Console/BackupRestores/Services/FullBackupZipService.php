<?php

namespace App\Modules\Console\BackupRestores\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
use ZipArchive;

class FullBackupZipService
{
    private const VERSION = 4;

    public function __construct(
        private readonly FullBackupArchiveValidator $archiveValidator,
        private readonly BackupSignatureService $signature,
        private readonly SqlDumpExecutor $sqlExecutor,
    ) {}

    public function create(string $databaseSql): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension belum aktif.');
        }

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);
        $path = $backupDir.DIRECTORY_SEPARATOR.'full-backup-'.now()->format('Ymd-His').'.zip';
        $storageFiles = $this->storageFiles();
        $entryHashes = ['database.sql' => hash('sha256', $databaseSql)];
        foreach ($storageFiles as $entry => $realPath) {
            $entryHashes[$entry] = hash_file('sha256', $realPath);
        }

        $manifest = [
            'schema' => 'laravel12-starterkit.full-backup',
            'version' => self::VERSION,
            'exported_at' => now()->toISOString(),
            'app' => ['name' => config('app.name'), 'url' => config('app.url'), 'environment' => config('app.env')],
            'database' => [
                'connection' => config('database.default'),
                'name' => config('database.connections.'.config('database.default').'.database'),
            ],
            'includes' => ['database.sql', 'storage_public'],
            'integrity' => [
                'database_sql_sha256' => $entryHashes['database.sql'],
                'entries_sha256' => $entryHashes,
            ],
        ];
        $manifest['authenticity'] = $this->signature->sign($manifest);

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat file backup ZIP.');
        }

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $zip->addFromString('database.sql', $databaseSql);
        foreach ($storageFiles as $entry => $realPath) {
            $zip->addFile($realPath, $entry);
        }
        $zip->close();

        return $path;
    }

    /** @return array{database_restored: bool, storage_files_restored: int} */
    public function restore(UploadedFile $file, bool $restoreDatabase, bool $restoreStoragePublic, bool $dryRun = false): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages(['backup' => 'PHP ZipArchive extension belum aktif.']);
        }

        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages(['backup' => 'File ZIP tidak bisa dibuka.']);
        }

        $this->archiveValidator->validate($zip);
        $manifestContent = $zip->getFromName('manifest.json');
        $manifest = $manifestContent ? json_decode($manifestContent, true) : null;

        if (! is_array($manifest) || ($manifest['schema'] ?? null) !== 'laravel12-starterkit.full-backup') {
            $zip->close();
            throw ValidationException::withMessages(['backup' => 'File ZIP bukan full backup aplikasi ini.']);
        }

        if (($manifest['version'] ?? null) !== self::VERSION) {
            $zip->close();
            throw ValidationException::withMessages(['backup' => 'Versi full backup tidak didukung. Buat backup baru dengan versi aplikasi saat ini.']);
        }

        try {
            $this->signature->verify($manifest);
            $this->verifyEntryIntegrity($zip, $manifest);
        } catch (Throwable $exception) {
            $zip->close();
            throw $exception;
        }

        $summary = ['database_restored' => false, 'storage_files_restored' => 0];
        if ($dryRun) {
            $zip->close();

            return $summary;
        }

        if ($restoreDatabase) {
            $sql = $zip->getFromName('database.sql');
            if (! $sql) {
                $zip->close();
                throw ValidationException::withMessages(['backup' => 'database.sql tidak ditemukan di dalam ZIP.']);
            }

            $this->sqlExecutor->run($sql);
            $summary['database_restored'] = true;
        }

        if ($restoreStoragePublic) {
            $summary['storage_files_restored'] = $this->extractStorage($zip);
        }

        $zip->close();

        return $summary;
    }

    /** @return array<string, string> */
    private function storageFiles(): array
    {
        $files = $this->filesUnder(storage_path('app/public'), 'storage_public');
        ksort($files, SORT_STRING);

        return $files;
    }

    /** @return array<string, string> */
    private function filesUnder(string $directory, string $archivePrefix): array
    {
        if (! File::isDirectory($directory)) {
            return [];
        }

        $files = [];
        foreach (File::allFiles($directory) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $files[$archivePrefix.'/'.$relative] = $file->getRealPath();
        }

        return $files;
    }

    /** @param array<string, mixed> $manifest */
    private function verifyEntryIntegrity(ZipArchive $zip, array $manifest): void
    {
        $expected = $manifest['integrity']['entries_sha256'] ?? null;
        if (! is_array($expected)) {
            throw ValidationException::withMessages(['backup' => 'Daftar checksum payload backup tidak ditemukan.']);
        }

        $actualEntries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === 'manifest.json' || str_ends_with($name, '/')) {
                continue;
            }
            $actualEntries[] = $name;
        }

        sort($actualEntries, SORT_STRING);
        $expectedEntries = array_keys($expected);
        sort($expectedEntries, SORT_STRING);
        if ($actualEntries !== $expectedEntries) {
            throw ValidationException::withMessages(['backup' => 'Daftar payload ZIP tidak sesuai dengan manifest yang ditandatangani.']);
        }

        foreach ($expected as $entry => $hash) {
            $contents = is_string($entry) ? $zip->getFromName($entry) : false;
            if (! is_string($hash) || ! is_string($contents) || ! hash_equals($hash, hash('sha256', $contents))) {
                throw ValidationException::withMessages(['backup' => "Checksum payload tidak valid: {$entry}."]);
            }
        }
    }

    private function extractStorage(ZipArchive $zip): int
    {
        $targets = [
            'storage_public/' => storage_path('app/public'),
        ];
        $restored = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $prefix = collect(array_keys($targets))->first(fn (string $candidate): bool => str_starts_with($name, $candidate));
            if ($prefix === null || str_ends_with($name, '/')) {
                continue;
            }

            $relative = substr($name, strlen($prefix));
            $destination = $targets[$prefix].DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            File::ensureDirectoryExists(dirname($destination));
            $contents = $zip->getFromIndex($i);
            if (! is_string($contents) || file_put_contents($destination, $contents, LOCK_EX) === false) {
                throw ValidationException::withMessages(['backup' => "Gagal mengekstrak file storage: {$name}."]);
            }
            $restored++;
        }

        return $restored;
    }
}
