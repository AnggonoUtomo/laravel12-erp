<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Modules\Console\SystemSettings\Infrastructure\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/settings/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_user_can_upload_a_profile_avatar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/profile', [
                '_method' => 'patch',
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 300, 300),
                'remove_avatar' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->refresh()->avatar);
        $this->assertSame('avatar', $user->getFirstMedia('avatar')?->collection_name);
    }

    public function test_profile_avatar_must_be_a_supported_image_within_the_size_limit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings/profile', [
                '_method' => 'patch',
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->create('avatar.svg', 2_049, 'image/svg+xml'),
            ])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->refresh()->avatar);
    }

    public function test_user_can_remove_their_profile_avatar(): void
    {
        $user = User::factory()->create();
        $user->addMedia(UploadedFile::fake()->image('avatar.jpg'))->toMediaCollection('avatar');

        $this->actingAs($user)
            ->patch('/settings/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'remove_avatar' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $this->assertNull($user->refresh()->avatar);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/settings/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertTrue(User::withTrashed()->find($user->id)?->trashed());
    }

    public function test_delete_account_section_is_visible_by_default(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/profile')
                ->where('accountDeletionEnabled', true));
    }

    public function test_delete_account_section_can_be_hidden_and_account_deletion_is_disabled(): void
    {
        $user = User::factory()->create();
        SystemSetting::query()->create([
            'group' => 'security_policy',
            'key' => 'allow_account_deletion',
            'value' => '0',
            'encrypted' => false,
        ]);

        $this->actingAs($user)
            ->get('/settings/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/profile')
                ->where('accountDeletionEnabled', false));

        $this->actingAs($user)
            ->delete('/settings/profile', ['password' => 'password'])
            ->assertForbidden();

        $this->assertNotNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->delete('/settings/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->fresh());
    }
}
