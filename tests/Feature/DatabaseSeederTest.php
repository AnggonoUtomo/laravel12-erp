<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_console_demo_users_and_roles(): void
    {
        $this->seed();

        $superSystem = User::where('email', 'anggono@mail.com')->firstOrFail();
        $admin = User::where('email', 'admin@mail.com')->firstOrFail();
        $staff = User::where('email', 'staff@mail.com')->firstOrFail();

        $this->assertTrue(Role::findByName('super-system')->hasPermissionTo('users.delete'));
        $this->assertTrue(Role::findByName('admin')->hasPermissionTo('users.create'));
        $this->assertTrue(Role::findByName('staff')->hasPermissionTo('users.view'));
        $this->assertFalse(Role::findByName('staff')->hasPermissionTo('users.create'));

        $this->assertTrue($superSystem->hasRole('super-system'));
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($staff->hasRole('staff'));
    }
}
