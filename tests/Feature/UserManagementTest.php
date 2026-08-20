<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Console\SystemSettings\Infrastructure\Jobs\SendUserActivationLinkJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private string $mediaDisk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mediaDisk = 'user-media-'.getmypid();
        config([
            "filesystems.disks.{$this->mediaDisk}" => [
                'driver' => 'local',
                'root' => storage_path("framework/testing/disks/{$this->mediaDisk}"),
                'throw' => false,
            ],
            'media-library.disk_name' => $this->mediaDisk,
        ]);
        Storage::fake($this->mediaDisk);

        foreach (['users.view', 'users.create', 'users.update', 'users.delete', 'users.restore', 'users.force-delete'] as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('admin')->syncPermissions(['users.view', 'users.create', 'users.update', 'users.delete', 'users.restore', 'users.force-delete']);
        Role::findOrCreate('staff')->syncPermissions(['users.view']);
    }

    public function test_authorized_users_can_view_user_management(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        Role::findOrCreate(User::SUPER_SYSTEM_ROLE)->syncPermissions(['users.view']);

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('roles', fn ($roles) => ! collect($roles)->pluck('name')->contains(User::SUPER_SYSTEM_ROLE))
                ->etc()
            );
    }

    public function test_super_system_user_can_view_super_system_role_in_user_management(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate(User::SUPER_SYSTEM_ROLE)->syncPermissions(['users.view']);
        $user->assignRole(User::SUPER_SYSTEM_ROLE);

        $this->actingAs($user)
            ->get(route('users.index', ['role' => User::SUPER_SYSTEM_ROLE]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('roles', fn ($roles) => collect($roles)->pluck('name')->contains(User::SUPER_SYSTEM_ROLE))
                ->where('users.data', fn ($users) => collect($users)->contains(fn ($listedUser) => collect($listedUser['roles'])->contains(User::SUPER_SYSTEM_ROLE)))
                ->where('filters.role', User::SUPER_SYSTEM_ROLE)
                ->etc()
            );
    }

    public function test_super_system_role_cannot_be_assigned_from_user_management(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        Role::findOrCreate(User::SUPER_SYSTEM_ROLE)->syncPermissions(['users.view']);

        $this->actingAs($user)
            ->post(route('users.store'), [
                'name' => 'Hidden Role Attempt',
                'email' => 'hidden.role@example.test',
                'roles' => [User::SUPER_SYSTEM_ROLE],
                'permissions' => [],
            ])
            ->assertSessionHasErrors('roles.0');
    }

    public function test_authorized_users_can_create_a_user_with_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->post(route('users.store'), [
                'name' => 'Team Member',
                'email' => 'team@example.com',
                'password' => 'password',
                'roles' => ['staff'],
            ])
            ->assertRedirect();

        $this->assertTrue(User::where('email', 'team@example.com')->first()?->hasRole('staff'));
    }

    public function test_user_activation_email_is_queued_when_email_automation_is_enabled(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('admin');

        \DB::table('system_settings')->insert([
            [
                'group' => 'email',
                'key' => 'enabled',
                'value' => '1',
                'encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'email',
                'key' => 'send_credentials_on_create',
                'value' => '1',
                'encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($user)
            ->post(route('users.store'), [
                'name' => 'Queued User',
                'email' => 'queued@example.com',
                'roles' => ['staff'],
            ])
            ->assertRedirect();

        Queue::assertPushedOn('mail', SendUserActivationLinkJob::class);
    }

    public function test_password_reset_link_is_queued_when_requested_on_user_update(): void
    {
        Queue::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $target = User::factory()->create([
            'password' => 'current-password',
        ]);
        $originalPassword = $target->password;

        \DB::table('system_settings')->insert([
            [
                'group' => 'email',
                'key' => 'enabled',
                'value' => '1',
                'encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'email',
                'key' => 'send_credentials_on_password_update',
                'value' => '1',
                'encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'send_password_reset_link' => true,
                'roles' => [],
                'permissions' => [],
            ])
            ->assertRedirect();

        Queue::assertPushedOn('mail', SendUserActivationLinkJob::class);
        $this->assertSame($originalPassword, $target->refresh()->password);
    }

    public function test_manual_password_payload_is_ignored_on_user_update(): void
    {
        Queue::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $target = User::factory()->create([
            'password' => 'current-password',
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'roles' => [],
                'permissions' => [],
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('current-password', $target->refresh()->password));
        $this->assertFalse(Hash::check('new-password', $target->password));
        Queue::assertNotPushed(SendUserActivationLinkJob::class);
    }

    public function test_authorized_users_can_create_a_user_with_avatar(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->post(route('users.store'), [
                'name' => 'Avatar User',
                'email' => 'avatar@example.com',
                'password' => 'password',
                'roles' => ['staff'],
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ])
            ->assertRedirect();

        $createdUser = User::where('email', 'avatar@example.com')->firstOrFail();

        $this->assertTrue($createdUser->hasMedia('avatar'));
        $this->assertNotNull($createdUser->avatar);
    }

    public function test_authorized_users_can_remove_an_existing_avatar(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $target = User::factory()->create();
        $target->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');

        $this->actingAs($admin)
            ->put(route('users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'roles' => [],
                'permissions' => [],
                'remove_avatar' => true,
            ])
            ->assertRedirect();

        $this->assertFalse($target->refresh()->hasMedia('avatar'));
    }

    public function test_authorized_users_can_soft_delete_and_restore_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target))
            ->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $target->id]);

        $this->actingAs($admin)
            ->patch(route('users.restore', $target->id))
            ->assertRedirect();

        $this->assertFalse($target->refresh()->trashed());
    }

    public function test_authorized_users_can_force_delete_archived_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $target = User::factory()->create();
        $target->delete();

        $this->actingAs($admin)
            ->delete(route('users.force-destroy', $target->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_users_without_permission_cannot_mutate_users(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($actor)->post(route('users.store'))->assertForbidden();
        $this->actingAs($actor)->put(route('users.update', $target))->assertForbidden();
        $this->actingAs($actor)->delete(route('users.destroy', $target))->assertForbidden();

        $target->delete();

        $this->actingAs($actor)->patch(route('users.restore', $target->id))->assertForbidden();
        $this->actingAs($actor)->delete(route('users.force-destroy', $target->id))->assertForbidden();
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }
}
