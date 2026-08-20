<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Console\AuditLogs\Models\AuditLog;
use App\Modules\Console\SystemSettings\Mail\SmtpTestMail;
use App\Modules\Console\SystemSettings\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['system-settings.view', 'system-settings.update', 'users.update'] as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('admin')->syncPermissions(['system-settings.view', 'system-settings.update', 'users.update']);
    }

    public function test_authorized_users_can_send_test_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->post(route('system-settings.email.test'), [
                'recipient' => 'test@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(SmtpTestMail::class, function (SmtpTestMail $mail) {
            return $mail->hasTo('test@example.com');
        });

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'module' => 'system-settings',
            'event' => 'email.test_succeeded',
            'description' => 'Sent SMTP test email to test@example.com',
        ]);
    }

    public function test_authorized_users_can_update_branding_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->post(route('system-settings.branding.update'), [
                '_method' => 'put',
                'app_name' => 'Urban Starter',
                'remove_logo' => false,
                'remove_favicon' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('system_settings', [
            'group' => 'branding',
            'key' => 'app_name',
            'value' => 'Urban Starter',
            'encrypted' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'module' => 'system-settings',
            'event' => 'branding.updated',
            'description' => 'Updated application branding',
        ]);
    }

    public function test_authorized_users_can_update_localization_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->put(route('system-settings.localization.update'), [
                'timezone' => 'Asia/Jakarta',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('system_settings', [
            'group' => 'localization',
            'key' => 'timezone',
            'value' => 'Asia/Jakarta',
            'encrypted' => false,
        ]);

        $this->assertDatabaseHas('system_settings', [
            'group' => 'localization',
            'key' => 'date_format',
            'value' => 'd/m/Y',
            'encrypted' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'module' => 'system-settings',
            'event' => 'localization.updated',
            'description' => 'Updated timezone and date format',
        ]);
    }

    public function test_authorized_users_can_update_pagination_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->put(route('system-settings.pagination.update'), [
                'default_per_page' => 25,
                'per_page_options' => [10, 25, 50],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('system_settings', [
            'group' => 'pagination',
            'key' => 'default_per_page',
            'value' => '25',
            'encrypted' => false,
        ]);

        $this->assertDatabaseHas('system_settings', [
            'group' => 'pagination',
            'key' => 'per_page_options',
            'value' => '10,25,50',
            'encrypted' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'module' => 'system-settings',
            'event' => 'pagination.updated',
            'description' => 'Updated default pagination settings',
        ]);
    }

    public function test_authorized_users_can_update_security_policy(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->put(route('system-settings.security-policy.update'), [
                'require_email_verification' => true,
                'audit_sensitive_actions' => true,
                'single_session_per_user' => false,
                'allow_account_deletion' => false,
                'session_lifetime_minutes' => 60,
                'login_max_attempts' => 7,
                'login_decay_minutes' => 10,
                'password_confirmation_timeout_seconds' => 3600,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('system_settings', [
            'group' => 'security_policy',
            'key' => 'login_max_attempts',
            'value' => '7',
            'encrypted' => false,
        ]);

        $this->assertDatabaseHas('system_settings', [
            'group' => 'security_policy',
            'key' => 'allow_account_deletion',
            'value' => '0',
            'encrypted' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'module' => 'system-settings',
            'event' => 'security_policy.updated',
            'description' => 'Updated security policy',
        ]);
    }

    public function test_authorized_users_can_update_password_policy(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->put(route('system-settings.password-policy.update'), [
                'min_length' => 12,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_symbols' => true,
                'uncompromised' => false,
                'expiry_days' => 90,
                'history_count' => 5,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('system_settings', [
            'group' => 'password_policy',
            'key' => 'min_length',
            'value' => '12',
            'encrypted' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'module' => 'system-settings',
            'event' => 'password_policy.updated',
            'description' => 'Updated password policy',
        ]);
    }

    public function test_password_policy_is_used_by_console_user_password_validation(): void
    {
        $admin = User::factory()->create([
            'password' => 'current-password',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->put(route('system-settings.password-policy.update'), [
                'min_length' => 12,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_symbols' => true,
                'uncompromised' => false,
                'expiry_days' => 0,
                'history_count' => 0,
            ]);

        $this->put(route('password.update'), [
            'current_password' => 'current-password',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors('password');
    }

    public function test_authorized_users_can_update_maintenance_mode(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->put(route('system-settings.maintenance-mode.update'), [
                'enabled' => true,
                'message' => 'Maintenance terjadwal malam ini.',
                'page_style' => 'operations',
                'retry_seconds' => 600,
                'refresh_seconds' => 30,
                'secret' => 'admin-bypass-2026',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('system_settings', [
            'group' => 'maintenance_mode',
            'key' => 'enabled',
            'value' => '1',
            'encrypted' => false,
        ]);

        $this->assertDatabaseHas('system_settings', [
            'group' => 'maintenance_mode',
            'key' => 'secret',
            'encrypted' => true,
        ]);

        $storedSecret = SystemSetting::query()
            ->where('group', 'maintenance_mode')
            ->where('key', 'secret')
            ->firstOrFail();

        $this->assertNotSame('admin-bypass-2026', $storedSecret->value);
        $this->assertSame('admin-bypass-2026', Crypt::decryptString($storedSecret->value));

        $this->assertDatabaseHas('system_settings', [
            'group' => 'maintenance_mode',
            'key' => 'page_style',
            'value' => 'operations',
            'encrypted' => false,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'module' => 'system-settings',
            'event' => 'maintenance_mode.updated',
            'description' => 'Updated maintenance mode',
        ]);

        $audit = AuditLog::query()->where('event', 'maintenance_mode.updated')->firstOrFail();

        $this->assertFalse(str_contains(json_encode($audit->new_values), 'admin-bypass-2026'));
        $this->assertNull($audit->new_values['bypass_url'] ?? null);
    }

    public function test_maintenance_secret_is_masked_in_props_and_blank_update_keeps_existing_secret(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->put(route('system-settings.maintenance-mode.update'), [
                'enabled' => true,
                'message' => 'Maintenance terjadwal.',
                'page_style' => 'operations',
                'retry_seconds' => 600,
                'refresh_seconds' => 30,
                'secret' => 'admin-bypass-2026',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('system-settings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('maintenanceMode.secret', null)
                ->where('maintenanceMode.secret_configured', true)
                ->where('maintenanceMode.bypass_url', null)
                ->etc()
            );

        $this->actingAs($user)
            ->put(route('system-settings.maintenance-mode.update'), [
                'enabled' => true,
                'message' => 'Maintenance terjadwal.',
                'page_style' => 'operations',
                'retry_seconds' => 600,
                'refresh_seconds' => 30,
                'secret' => '',
            ])
            ->assertRedirect();

        $storedSecret = SystemSetting::query()
            ->where('group', 'maintenance_mode')
            ->where('key', 'secret')
            ->firstOrFail();

        $this->assertSame('admin-bypass-2026', Crypt::decryptString($storedSecret->value));
    }

    public function test_system_settings_remains_reachable_during_maintenance_mode(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Artisan::call('down', [
            '--retry' => 300,
            '--secret' => 'admin-bypass-2026',
        ]);

        try {
            $this->actingAs($user)
                ->get(route('system-settings.index'))
                ->assertOk();
        } finally {
            Artisan::call('up');
        }
    }

    public function test_users_without_permission_cannot_mutate_system_settings(): void
    {
        $user = User::factory()->create();
        $requests = [
            ['put', 'system-settings.branding.update'],
            ['put', 'system-settings.email.update'],
            ['post', 'system-settings.email.test'],
            ['put', 'system-settings.localization.update'],
            ['put', 'system-settings.pagination.update'],
            ['put', 'system-settings.security-policy.update'],
            ['put', 'system-settings.password-policy.update'],
            ['put', 'system-settings.maintenance-mode.update'],
        ];

        foreach ($requests as [$method, $routeName]) {
            $this->actingAs($user)->{$method}(route($routeName))->assertForbidden();
        }

        $this->assertDatabaseCount('system_settings', 0);
    }
}
