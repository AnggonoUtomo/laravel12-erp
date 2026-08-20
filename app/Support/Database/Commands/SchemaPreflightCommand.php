<?php

namespace App\Support\Database\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SchemaPreflightCommand extends Command
{
    private const IDENTITY_TABLES = [
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'media',
        'audit_logs',
        'system_settings',
        'notification_templates',
        'login_activities',
        'sessions',
    ];

    protected $signature = 'schema:preflight';

    protected $description = 'Check that the active schema is safe to reconcile before the ULID migration.';

    public function handle(): int
    {
        $activeTables = collect(self::IDENTITY_TABLES)
            ->filter(fn (string $table): bool => Schema::hasTable($table))
            ->values();

        if ($activeTables->isEmpty()) {
            $this->components->info('Tidak ada tabel identity aplikasi yang aktif. Preflight lulus.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('migrations')) {
            $this->components->error('Migration ledger tidak ditemukan; cutover ULID diblokir.');

            return self::FAILURE;
        }

        if (DB::table('migrations')->count() === 0) {
            $this->components->error('Migration ledger kosong sementara tabel identity aplikasi masih aktif; cutover ULID diblokir.');
            $this->line('Tabel terdeteksi: '.$activeTables->implode(', '));

            return self::FAILURE;
        }

        $migrationFiles = collect(File::files(database_path('migrations')))
            ->map(fn ($file): string => pathinfo($file->getFilename(), PATHINFO_FILENAME))
            ->all();
        $staleMigrations = DB::table('migrations')
            ->pluck('migration')
            ->diff($migrationFiles)
            ->values();

        if ($staleMigrations->isNotEmpty()) {
            $this->components->error('Migration ledger merujuk migration file tidak ditemukan; cutover ULID diblokir.');
            $this->line('Migration stale: '.$staleMigrations->implode(', '));

            return self::FAILURE;
        }

        $this->components->info('Schema preflight lulus.');

        return self::SUCCESS;
    }
}
