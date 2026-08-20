<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Console\AuditLogs\Models\AuditLog;
use App\Modules\Console\AuditLogs\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['audit-logs.view', 'users.create', 'users.view'] as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('admin')->syncPermissions(['audit-logs.view', 'users.create', 'users.view']);
        Role::findOrCreate('staff');
    }

    public function test_authorized_users_can_view_audit_logs(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        AuditLog::query()->create([
            'actor_id' => $user->id,
            'module' => 'testing',
            'event' => 'testing.created',
            'description' => 'Created testing data',
        ]);

        $this->actingAs($user)
            ->get(route('audit-logs.index'))
            ->assertOk();
    }

    public function test_users_without_permission_cannot_view_audit_logs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }

    public function test_user_creation_writes_audit_log(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Audited User',
                'email' => 'audited@example.com',
                'roles' => ['staff'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'module' => 'user-management',
            'event' => 'user.created',
            'description' => 'Created user audited@example.com',
        ]);
    }

    public function test_audit_log_service_redacts_sensitive_values_recursively(): void
    {
        app(AuditLogService::class)->record(
            module: 'testing',
            event: 'testing.redacted',
            newValues: [
                'email' => 'safe@example.com',
                'password' => 'plain-secret',
                'reset_token' => 'token-secret',
                'nested' => [
                    'smtp_password' => 'smtp-secret',
                    'external_service_api_key' => 'service-secret',
                    'visible' => 'safe-value',
                ],
            ],
            fallbackToAuthenticatedActor: false,
        );

        $values = AuditLog::query()->where('event', 'testing.redacted')->firstOrFail()->new_values;

        $this->assertSame('safe@example.com', $values['email']);
        $this->assertSame('[redacted]', $values['password']);
        $this->assertSame('[redacted]', $values['reset_token']);
        $this->assertSame('[redacted]', $values['nested']['smtp_password']);
        $this->assertSame('[redacted]', $values['nested']['external_service_api_key']);
        $this->assertSame('safe-value', $values['nested']['visible']);
    }
}
