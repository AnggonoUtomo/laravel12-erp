<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const PRIMARY_TABLES = ['users', 'roles', 'permissions', 'media', 'audit_logs', 'system_settings', 'notification_templates', 'login_activities'];

    public function up(): void
    {
        foreach (self::PRIMARY_TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->ulid('ulid')->nullable()->unique();
            });

            DB::table($table)->orderBy('id')->each(function (object $row) use ($table): void {
                DB::table($table)->where('id', $row->id)->update(['ulid' => (string) Str::ulid()]);
            });
        }

        foreach (['audit_logs' => 'actor_ulid', 'login_activities' => 'user_ulid', 'sessions' => 'user_ulid'] as $table => $column) {
            Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                $blueprint->ulid($column)->nullable()->index();
            });
        }

        DB::table('audit_logs')->whereNotNull('actor_id')->update(['actor_ulid' => DB::raw('(SELECT ulid FROM users WHERE users.id = audit_logs.actor_id)')]);
        DB::table('login_activities')->whereNotNull('user_id')->update(['user_ulid' => DB::raw('(SELECT ulid FROM users WHERE users.id = login_activities.user_id)')]);
        DB::table('sessions')->whereNotNull('user_id')->update(['user_ulid' => DB::raw('(SELECT ulid FROM users WHERE users.id = sessions.user_id)')]);
    }

    public function down(): void
    {
        foreach (['audit_logs' => 'actor_ulid', 'login_activities' => 'user_ulid', 'sessions' => 'user_ulid'] as $table => $column) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($column));
        }

        foreach (self::PRIMARY_TABLES as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('ulid'));
        }
    }
};
