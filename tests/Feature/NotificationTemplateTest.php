<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Console\NotificationTemplates\Application\Services\NotificationTemplateService;
use App\Modules\Console\NotificationTemplates\Infrastructure\Models\NotificationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['notification-templates.view', 'notification-templates.update'] as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('admin')->syncPermissions(['notification-templates.view', 'notification-templates.update']);
    }

    public function test_authorized_users_can_view_notification_templates(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('notification-templates.index'))
            ->assertOk();

        $this->assertDatabaseHas('notification_templates', [
            'key' => 'user.activation',
        ]);
    }

    public function test_authorized_users_can_update_notification_template(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        app(NotificationTemplateService::class)->ensureDefaults();
        $template = NotificationTemplate::query()->where('key', 'user.activation')->firstOrFail();

        $this->actingAs($user)
            ->put(route('notification-templates.update', $template), [
                'subject' => 'Subject baru',
                'body' => 'Halo {{ name }}',
                'active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notification_templates', [
            'id' => $template->id,
            'subject' => 'Subject baru',
            'body' => 'Halo {{ name }}',
            'active' => true,
        ]);
    }

    public function test_users_without_permission_cannot_update_notification_templates(): void
    {
        app(NotificationTemplateService::class)->ensureDefaults();
        $template = NotificationTemplate::query()->where('key', 'user.activation')->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->put(route('notification-templates.update', $template))
            ->assertForbidden();
    }
}
