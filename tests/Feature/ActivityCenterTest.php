<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Console\AuditLogs\Infrastructure\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('activity-center.view');
        Permission::findOrCreate('audit-logs.view');

        Role::findOrCreate('admin')->syncPermissions(['activity-center.view', 'audit-logs.view']);
    }

    public function test_activity_center_summary_is_shared_for_authorized_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        AuditLog::query()->create([
            'actor_id' => $user->id,
            'module' => 'users',
            'event' => 'created',
            'description' => 'Created a user',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('activity_center.unread_count', 1)
                ->where('activity_center.items.0.description', 'Created a user')
                ->where('activity_center.items.0.unread', true)
            );
    }

    public function test_authorized_users_can_mark_activity_center_as_read(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        AuditLog::query()->create([
            'actor_id' => $user->id,
            'module' => 'system-settings',
            'event' => 'pagination.updated',
            'description' => 'Updated default pagination settings',
        ]);

        $this->actingAs($user)
            ->post(route('activity-center.read'))
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->activity_center_read_at);
    }

    public function test_unauthorized_users_cannot_mark_activity_center_as_read(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('activity-center.read'))
            ->assertForbidden();
    }
}
