<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Console\LoginActivities\Infrastructure\Models\LoginActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('login-activities.view');
        Role::findOrCreate('admin')->syncPermissions(['login-activities.view']);
    }

    public function test_successful_login_is_recorded(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login'), [
            'email' => 'login@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('login_activities', [
            'user_id' => $user->id,
            'email' => 'login@example.com',
            'event' => 'login',
            'successful' => true,
        ]);
    }

    public function test_failed_login_is_recorded(): void
    {
        User::factory()->create([
            'email' => 'failed@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'failed@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('login_activities', [
            'email' => 'failed@example.com',
            'event' => 'login_failed',
            'successful' => false,
        ]);
    }

    public function test_authorized_users_can_view_login_activities(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        LoginActivity::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'event' => 'login',
            'successful' => true,
            'occurred_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('login-activities.index'))
            ->assertOk();
    }

    public function test_users_without_permission_cannot_view_login_activities(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('login-activities.index'))
            ->assertForbidden();
    }

    public function test_logout_is_recorded(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.com',
        ]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertDatabaseHas('login_activities', [
            'user_id' => $user->id,
            'email' => 'logout@example.com',
            'event' => 'logout',
            'successful' => true,
        ]);
    }

    public function test_login_activity_does_not_store_submitted_password(): void
    {
        User::factory()->create([
            'email' => 'safe-login@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'safe-login@example.com',
                'password' => 'very-sensitive-wrong-password',
            ])
            ->assertRedirect(route('login'));

        $activity = LoginActivity::query()->where('email', 'safe-login@example.com')->firstOrFail();

        $this->assertStringNotContainsString('very-sensitive-wrong-password', (string) $activity->message);
        $this->assertStringNotContainsString('very-sensitive-wrong-password', (string) $activity->user_agent);
    }
}
