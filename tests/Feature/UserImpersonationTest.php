<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Console\UserManagements\Application\Services\UserImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users.view', 'users.impersonate'] as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('admin')->syncPermissions(['users.view', 'users.impersonate']);
        Role::findOrCreate('staff')->syncPermissions(['users.view']);
        Role::findOrCreate('super-system')->syncPermissions(['users.view', 'users.impersonate']);
    }

    public function test_authorized_user_can_start_and_stop_impersonation(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('admin');

        $target = User::factory()->create(['email' => 'target@example.com']);
        $target->assignRole('staff');

        $this->actingAs($admin)
            ->post(route('users.impersonate', $target))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($target);
        $this->assertSame($admin->id, session(UserImpersonationService::SESSION_IMPERSONATOR_ID));

        $this->post(route('users.impersonate.stop'))
            ->assertRedirect(route('users.index'));

        $this->assertAuthenticatedAs($admin);
        $this->assertFalse(session()->has(UserImpersonationService::SESSION_IMPERSONATOR_ID));
    }

    public function test_super_system_users_cannot_be_impersonated(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create();
        $target->assignRole('super-system');

        $this->actingAs($admin)
            ->post(route('users.impersonate', $target))
            ->assertRedirect()
            ->assertSessionHas('error', 'Tidak bisa impersonate akun super-system.');
    }

    public function test_user_cannot_impersonate_themself_even_when_super_system(): void
    {
        $superSystem = User::factory()->create();
        $superSystem->assignRole('super-system');

        $this->actingAs($superSystem)
            ->post(route('users.impersonate', $superSystem))
            ->assertRedirect()
            ->assertSessionHas('error', 'Tidak bisa impersonate akun sendiri.');

        $this->assertAuthenticatedAs($superSystem);
        $this->assertFalse(session()->has(UserImpersonationService::SESSION_IMPERSONATOR_ID));
    }

    public function test_super_system_can_impersonate_non_super_system_user(): void
    {
        $superSystem = User::factory()->create();
        $superSystem->assignRole('super-system');

        $target = User::factory()->create();
        $target->assignRole('admin');

        $this->actingAs($superSystem)
            ->post(route('users.impersonate', $target))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($target);
    }

    public function test_user_without_permission_gets_friendly_impersonation_error(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $target = User::factory()->create();
        $target->assignRole('admin');

        $this->actingAs($staff)
            ->post(route('users.impersonate', $target))
            ->assertRedirect()
            ->assertSessionHas('error', 'Akun kamu belum memiliki permission users.impersonate.');

        $this->assertAuthenticatedAs($staff);
    }
}
