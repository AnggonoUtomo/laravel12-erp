<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/console/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('console/dashboard')
                ->has('dashboard.console')
                ->has('dashboard.activity')
                ->where('dashboard.console.users', 0)
            );
    }

    public function test_dashboard_metrics_respect_permissions()
    {
        Permission::findOrCreate('users.view');
        Permission::findOrCreate('audit-logs.view');
        Permission::findOrCreate('login-activities.view');

        $role = Role::findOrCreate('dashboard-observer');
        $role->syncPermissions([
            'users.view',
            'audit-logs.view',
            'login-activities.view',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('dashboard.access.console_admin', true)
                ->where('dashboard.access.audit', true)
                ->where('dashboard.access.login_activities', true)
                ->where('dashboard.console.users', 1)
            );
    }
}
