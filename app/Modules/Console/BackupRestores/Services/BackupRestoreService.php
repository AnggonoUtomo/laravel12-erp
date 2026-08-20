<?php

namespace App\Modules\Console\BackupRestores\Services;

use App\Modules\Console\AuditLogs\Application\Services\AuditLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BackupRestoreService
{
    private const VERSION = 1;

    public function __construct(
        private readonly AuditLogService $audit,
        private readonly SettingsBackupService $settingsBackup,
        private readonly FullBackupZipService $zipService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return $this->settingsBackup->overview();
    }

    /** @return array<string, mixed> */
    public function export(): array
    {
        return $this->settingsBackup->export();
    }

    /** @return array<string, int> */
    public function restore(UploadedFile $file, bool $restoreSystemSettings, bool $restoreNotificationTemplates): array
    {
        return $this->settingsBackup->restore($file, $restoreSystemSettings, $restoreNotificationTemplates);
    }

    public function createFullBackupZip(): string
    {
        $databaseSql = $this->databaseDumpSql();
        $path = $this->zipService->create($databaseSql);

        $this->audit->record(
            module: 'backup-restore',
            event: 'full_backup.exported',
            description: 'Exported full database and storage backup',
            newValues: [
                'path' => basename($path),
                'database' => config('database.default'),
                'storage_public_size' => $this->directorySize(storage_path('app/public')),
            ],
        );

        return $path;
    }

    /**
     * @return array<string, int|bool>
     */
    public function restoreFullBackup(UploadedFile $file, bool $restoreDatabase, bool $restoreStoragePublic, bool $dryRun = false): array
    {
        if (! $dryRun && ! $restoreDatabase && ! $restoreStoragePublic) {
            throw ValidationException::withMessages([
                'backup' => 'Pilih minimal database atau storage file untuk full restore.',
            ]);
        }

        $summary = $this->zipService->restore($file, $restoreDatabase, $restoreStoragePublic, $dryRun);

        $this->audit->record(
            module: 'backup-restore',
            event: $dryRun ? 'full_backup.dry_run_validated' : 'full_backup.restored',
            description: $dryRun ? 'Validated full backup dry-run' : 'Restored full backup',
            newValues: $summary + ['dry_run' => $dryRun],
        );

        return $summary;
    }

    private function databaseDumpSql(): string
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            return $this->sqliteDumpSql();
        }

        if ($driver !== 'mysql') {
            throw new RuntimeException('Full database backup saat ini mendukung koneksi MySQL dan SQLite.');
        }

        $pdo = $connection->getPdo();
        $database = $connection->getDatabaseName();
        $tables = collect($connection->select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']))
            ->map(fn (object $row) => array_values((array) $row)[0])
            ->values();

        $sql = [
            '-- Laravel 12 Starterkit full database backup',
            '-- Exported at: '.now()->toISOString(),
            '-- Database: '.$database,
            'SET FOREIGN_KEY_CHECKS=0;',
            'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";',
            '',
        ];

        foreach ($tables as $table) {
            $quotedTable = $this->quoteIdentifier($table);
            $create = (array) $connection->selectOne("SHOW CREATE TABLE {$quotedTable}");
            $createStatement = $create['Create Table'] ?? array_values($create)[1] ?? null;

            if (! $createStatement) {
                continue;
            }

            $sql[] = "DROP TABLE IF EXISTS {$quotedTable};";
            $sql[] = $createStatement.';';
            $sql[] = '';

            $connection->table($table)->orderByRaw('1')->chunk(500, function ($rows) use (&$sql, $pdo, $quotedTable) {
                foreach ($rows as $row) {
                    $values = (array) $row;
                    $columns = collect(array_keys($values))->map(fn (string $column) => $this->quoteIdentifier($column))->implode(', ');
                    $serializedValues = collect($values)
                        ->map(fn (mixed $value) => $value === null ? 'NULL' : $pdo->quote((string) $value))
                        ->implode(', ');

                    $sql[] = "INSERT INTO {$quotedTable} ({$columns}) VALUES ({$serializedValues});";
                }

                if ($rows->isNotEmpty()) {
                    $sql[] = '';
                }
            });
        }

        $sql[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $sql[] = '';

        return implode(PHP_EOL, $sql);
    }

    private function sqliteDumpSql(): string
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $tables = collect($connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"))
            ->pluck('name')
            ->values();

        $sql = [
            '-- Laravel 12 Starterkit full database backup',
            '-- Exported at: '.now()->toISOString(),
            '-- Driver: sqlite',
            'PRAGMA foreign_keys=OFF;',
            'BEGIN TRANSACTION;',
            '',
        ];

        foreach ($tables as $table) {
            $quotedTable = $this->quoteSqliteIdentifier($table);
            $schema = $connection->selectOne('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', $table]);
            $createStatement = $schema?->sql;

            if (! $createStatement) {
                continue;
            }

            $sql[] = "DROP TABLE IF EXISTS {$quotedTable};";
            $sql[] = $createStatement.';';

            foreach ($connection->table($table)->get() as $row) {
                $values = (array) $row;
                $columns = collect(array_keys($values))->map(fn (string $column) => $this->quoteSqliteIdentifier($column))->implode(', ');
                $serializedValues = collect($values)
                    ->map(fn (mixed $value) => $value === null ? 'NULL' : $pdo->quote((string) $value))
                    ->implode(', ');

                $sql[] = "INSERT INTO {$quotedTable} ({$columns}) VALUES ({$serializedValues});";
            }

            $sql[] = '';
        }

        $sql[] = 'COMMIT;';
        $sql[] = 'PRAGMA foreign_keys=ON;';
        $sql[] = '';

        return implode(PHP_EOL, $sql);
    }

    private function directorySize(string $directory): int
    {
        if (! File::isDirectory($directory)) {
            return 0;
        }

        return collect(File::allFiles($directory))->sum(fn ($file) => $file->getSize());
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function quoteSqliteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
}
