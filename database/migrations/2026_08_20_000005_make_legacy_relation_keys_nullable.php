<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::unprepared('ALTER TABLE model_has_roles CHANGE legacy_role_id legacy_role_id BIGINT UNSIGNED NULL, CHANGE legacy_model_id legacy_model_id BIGINT UNSIGNED NULL');
        DB::unprepared('ALTER TABLE model_has_permissions CHANGE legacy_permission_id legacy_permission_id BIGINT UNSIGNED NULL, CHANGE legacy_model_id legacy_model_id BIGINT UNSIGNED NULL');
        DB::unprepared('ALTER TABLE role_has_permissions CHANGE legacy_permission_id legacy_permission_id BIGINT UNSIGNED NULL, CHANGE legacy_role_id legacy_role_id BIGINT UNSIGNED NULL');
        DB::unprepared('ALTER TABLE media CHANGE legacy_model_id legacy_model_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        throw new \LogicException('Rollback key ULID harus memakai full-backup terverifikasi agar relasi dan data audit dapat dipulihkan secara atomik.');
    }
};
