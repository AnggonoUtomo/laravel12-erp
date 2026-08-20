<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var list<string> */
    private const PRIMARY_TABLES = [
        'users',
        'roles',
        'permissions',
        'media',
        'audit_logs',
        'system_settings',
        'notification_templates',
        'login_activities',
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $this->assertTransitionIsComplete();

        DB::unprepared('ALTER TABLE model_has_roles DROP FOREIGN KEY model_has_roles_role_id_foreign, DROP PRIMARY KEY, DROP INDEX model_has_roles_model_id_model_type_index, CHANGE role_id legacy_role_id BIGINT UNSIGNED NULL, CHANGE model_id legacy_model_id BIGINT UNSIGNED NULL, CHANGE role_ulid role_id CHAR(26) NOT NULL, CHANGE model_ulid model_id CHAR(26) NOT NULL, ADD PRIMARY KEY (role_id, model_id, model_type), ADD INDEX model_has_roles_model_id_model_type_index (model_id, model_type), ADD INDEX model_has_roles_legacy_role_id_index (legacy_role_id), ADD INDEX model_has_roles_legacy_model_id_index (legacy_model_id)');
        DB::unprepared('ALTER TABLE model_has_permissions DROP FOREIGN KEY model_has_permissions_permission_id_foreign, DROP PRIMARY KEY, DROP INDEX model_has_permissions_model_id_model_type_index, CHANGE permission_id legacy_permission_id BIGINT UNSIGNED NULL, CHANGE model_id legacy_model_id BIGINT UNSIGNED NULL, CHANGE permission_ulid permission_id CHAR(26) NOT NULL, CHANGE model_ulid model_id CHAR(26) NOT NULL, ADD PRIMARY KEY (permission_id, model_id, model_type), ADD INDEX model_has_permissions_model_id_model_type_index (model_id, model_type), ADD INDEX model_has_permissions_legacy_permission_id_index (legacy_permission_id), ADD INDEX model_has_permissions_legacy_model_id_index (legacy_model_id)');
        DB::unprepared('ALTER TABLE role_has_permissions DROP FOREIGN KEY role_has_permissions_permission_id_foreign, DROP FOREIGN KEY role_has_permissions_role_id_foreign, DROP PRIMARY KEY, DROP INDEX role_has_permissions_role_id_foreign, CHANGE permission_id legacy_permission_id BIGINT UNSIGNED NULL, CHANGE role_id legacy_role_id BIGINT UNSIGNED NULL, CHANGE permission_ulid permission_id CHAR(26) NOT NULL, CHANGE role_ulid role_id CHAR(26) NOT NULL, ADD PRIMARY KEY (permission_id, role_id), ADD INDEX role_has_permissions_legacy_permission_id_index (legacy_permission_id), ADD INDEX role_has_permissions_legacy_role_id_index (legacy_role_id)');

        DB::unprepared('ALTER TABLE sessions DROP INDEX sessions_user_id_index, CHANGE user_id legacy_user_id BIGINT UNSIGNED NULL, CHANGE user_ulid user_id CHAR(26) NULL, ADD INDEX sessions_user_id_index (user_id), ADD INDEX sessions_legacy_user_id_index (legacy_user_id)');
        DB::unprepared('ALTER TABLE login_activities DROP FOREIGN KEY login_activities_user_id_foreign, DROP INDEX login_activities_user_id_foreign, CHANGE user_id legacy_user_id BIGINT UNSIGNED NULL, CHANGE user_ulid user_id CHAR(26) NULL, ADD INDEX login_activities_user_id_foreign (user_id), ADD INDEX login_activities_legacy_user_id_index (legacy_user_id)');
        DB::unprepared('ALTER TABLE audit_logs DROP FOREIGN KEY audit_logs_actor_id_foreign, DROP INDEX audit_logs_actor_id_foreign, DROP INDEX audit_logs_auditable_type_auditable_id_index, CHANGE actor_id legacy_actor_id BIGINT UNSIGNED NULL, CHANGE auditable_id legacy_auditable_id BIGINT UNSIGNED NULL, CHANGE actor_ulid actor_id CHAR(26) NULL, CHANGE auditable_ulid auditable_id CHAR(26) NULL, ADD INDEX audit_logs_actor_id_foreign (actor_id), ADD INDEX audit_logs_auditable_type_auditable_id_index (auditable_type, auditable_id), ADD INDEX audit_logs_legacy_actor_id_index (legacy_actor_id), ADD INDEX audit_logs_legacy_auditable_id_index (legacy_auditable_id)');
        DB::unprepared('ALTER TABLE media DROP INDEX media_model_type_model_id_index, CHANGE model_id legacy_model_id BIGINT UNSIGNED NULL, CHANGE model_ulid model_id CHAR(26) NOT NULL, ADD INDEX media_model_type_model_id_index (model_type, model_id), ADD INDEX media_legacy_model_id_index (legacy_model_id)');

        foreach (self::PRIMARY_TABLES as $table) {
            $this->switchPrimaryKey($table);
        }

        DB::unprepared('ALTER TABLE model_has_roles ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE model_has_permissions ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE role_has_permissions ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE, ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE login_activities ADD CONSTRAINT login_activities_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
        DB::unprepared('ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_actor_id_foreign FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        throw new \LogicException('Rollback key ULID harus memakai full-backup terverifikasi agar relasi dan data audit dapat dipulihkan secara atomik.');
    }

    private function switchPrimaryKey(string $table): void
    {
        DB::unprepared("ALTER TABLE {$table} DROP PRIMARY KEY, DROP INDEX {$table}_ulid_unique, CHANGE id legacy_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, CHANGE ulid id CHAR(26) NOT NULL, ADD PRIMARY KEY (id), ADD UNIQUE {$table}_legacy_id_unique (legacy_id)");
    }

    private function assertTransitionIsComplete(): void
    {
        foreach (self::PRIMARY_TABLES as $table) {
            if (DB::table($table)->whereNull('ulid')->exists()) {
                throw new \LogicException("ULID kosong ditemukan pada {$table}; cutover dibatalkan.");
            }
        }

        $checks = [
            ['model_has_roles', 'role_ulid'],
            ['model_has_roles', 'model_ulid'],
            ['model_has_permissions', 'permission_ulid'],
            ['model_has_permissions', 'model_ulid'],
            ['role_has_permissions', 'role_ulid'],
            ['role_has_permissions', 'permission_ulid'],
            ['media', 'model_ulid'],
        ];

        foreach ($checks as [$table, $column]) {
            if (DB::table($table)->whereNull($column)->exists()) {
                throw new \LogicException("Referensi ULID kosong ditemukan pada {$table}.{$column}; cutover dibatalkan.");
            }
        }

        foreach ([['sessions', 'user_id', 'user_ulid'], ['login_activities', 'user_id', 'user_ulid'], ['audit_logs', 'actor_id', 'actor_ulid']] as [$table, $legacy, $ulid]) {
            if (DB::table($table)->whereNotNull($legacy)->whereNull($ulid)->exists()) {
                throw new \LogicException("Referensi ULID kosong ditemukan pada {$table}.{$ulid}; cutover dibatalkan.");
            }
        }

        if (DB::table('audit_logs')->whereNotNull('auditable_id')->whereNull('auditable_ulid')->exists()) {
            throw new \LogicException('Referensi ULID kosong ditemukan pada audit_logs.auditable_ulid; cutover dibatalkan.');
        }
    }
};
