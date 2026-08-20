<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaPreflightCommandTest extends TestCase
{
    public function test_it_fails_when_active_application_tables_exist_without_migration_ledger_entries(): void
    {
        Schema::create('migrations', function (Blueprint $table): void {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->ulid('id')->primary();
        });

        $this->artisan('schema:preflight')
            ->expectsOutputToContain('Migration ledger kosong')
            ->assertExitCode(1);
    }

    public function test_it_fails_when_the_migration_ledger_references_a_missing_migration_file(): void
    {
        Schema::create('migrations', function (Blueprint $table): void {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->ulid('id')->primary();
        });
        \DB::table('migrations')->insert(['migration' => 'legacy_create_hr_employees_table', 'batch' => 1]);

        $this->artisan('schema:preflight')
            ->expectsOutputToContain('migration file tidak ditemukan')
            ->assertExitCode(1);
    }
}
