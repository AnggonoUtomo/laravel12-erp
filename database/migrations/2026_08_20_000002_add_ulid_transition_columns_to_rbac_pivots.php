<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'model_has_roles' => ['role_ulid', 'model_ulid'],
            'model_has_permissions' => ['permission_ulid', 'model_ulid'],
            'role_has_permissions' => ['role_ulid', 'permission_ulid'],
        ] as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $column) {
                    $blueprint->ulid($column)->nullable()->index();
                }
            });
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('UPDATE model_has_roles SET role_ulid = (SELECT ulid FROM roles WHERE roles.id = model_has_roles.role_id)');
            DB::statement("UPDATE model_has_roles SET model_ulid = (SELECT ulid FROM users WHERE users.id = model_has_roles.model_id) WHERE model_type = 'App\\Models\\User'");
            DB::statement('UPDATE model_has_permissions SET permission_ulid = (SELECT ulid FROM permissions WHERE permissions.id = model_has_permissions.permission_id)');
            DB::statement("UPDATE model_has_permissions SET model_ulid = (SELECT ulid FROM users WHERE users.id = model_has_permissions.model_id) WHERE model_type = 'App\\Models\\User'");
            DB::statement('UPDATE role_has_permissions SET role_ulid = (SELECT ulid FROM roles WHERE roles.id = role_has_permissions.role_id)');
            DB::statement('UPDATE role_has_permissions SET permission_ulid = (SELECT ulid FROM permissions WHERE permissions.id = role_has_permissions.permission_id)');

            return;
        }

        DB::statement('UPDATE model_has_roles JOIN roles ON roles.id = model_has_roles.role_id SET model_has_roles.role_ulid = roles.ulid');
        DB::statement('UPDATE model_has_roles JOIN users ON users.id = model_has_roles.model_id SET model_has_roles.model_ulid = users.ulid WHERE model_has_roles.model_type = \'App\\\\Models\\\\User\'');
        DB::statement('UPDATE model_has_permissions JOIN permissions ON permissions.id = model_has_permissions.permission_id SET model_has_permissions.permission_ulid = permissions.ulid');
        DB::statement('UPDATE model_has_permissions JOIN users ON users.id = model_has_permissions.model_id SET model_has_permissions.model_ulid = users.ulid WHERE model_has_permissions.model_type = \'App\\\\Models\\\\User\'');
        DB::statement('UPDATE role_has_permissions JOIN roles ON roles.id = role_has_permissions.role_id SET role_has_permissions.role_ulid = roles.ulid');
        DB::statement('UPDATE role_has_permissions JOIN permissions ON permissions.id = role_has_permissions.permission_id SET role_has_permissions.permission_ulid = permissions.ulid');
    }

    public function down(): void
    {
        foreach ([
            'model_has_roles' => ['role_ulid', 'model_ulid'],
            'model_has_permissions' => ['permission_ulid', 'model_ulid'],
            'role_has_permissions' => ['role_ulid', 'permission_ulid'],
        ] as $table => $columns) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($columns));
        }
    }
};
