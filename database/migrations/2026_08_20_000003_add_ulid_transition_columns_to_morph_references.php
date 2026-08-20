<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->ulid('model_ulid')->nullable()->index();
        });
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->ulid('auditable_ulid')->nullable()->index();
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE media SET model_ulid = (SELECT ulid FROM users WHERE users.id = media.model_id) WHERE model_type = 'App\\Models\\User'");

            return;
        }

        DB::statement("UPDATE media JOIN users ON users.id = media.model_id SET media.model_ulid = users.ulid WHERE media.model_type = 'App\\\\Models\\\\User'");
    }

    public function down(): void
    {
        Schema::table('media', fn (Blueprint $table) => $table->dropColumn('model_ulid'));
        Schema::table('audit_logs', fn (Blueprint $table) => $table->dropColumn('auditable_ulid'));
    }
};
