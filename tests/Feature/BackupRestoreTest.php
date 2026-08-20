<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Console\BackupRestores\Services\FullBackupZipService;
use App\Modules\Console\SystemSettings\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'backup.signature_key' => 'testing-shared-backup-signature-key-32-bytes',
            'backup.signature_key_id' => 'test-primary',
        ]);

        foreach (['backup-restore.view', 'backup-restore.export', 'backup-restore.restore'] as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (['backup-restore.full-export', 'backup-restore.full-restore'] as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('admin')->syncPermissions([
            'backup-restore.view',
            'backup-restore.export',
            'backup-restore.restore',
            'backup-restore.full-export',
            'backup-restore.full-restore',
        ]);
    }

    public function test_authorized_users_can_view_backup_restore_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('backup-restore.index'))
            ->assertOk();
    }

    public function test_guests_cannot_access_backup_restore_endpoints(): void
    {
        $this->get(route('backup-restore.index'))->assertRedirect(route('login'));
        $this->get(route('backup-restore.export'))->assertRedirect(route('login'));
        $this->post(route('backup-restore.restore'))->assertRedirect(route('login'));
        $this->get(route('backup-restore.full.export'))->assertRedirect(route('login'));
        $this->post(route('backup-restore.full.restore'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_without_permissions_cannot_access_backup_restore_endpoints(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('backup-restore.index'))->assertForbidden();
        $this->actingAs($user)->get(route('backup-restore.export'))->assertForbidden();
        $this->actingAs($user)->post(route('backup-restore.restore'))->assertForbidden();
        $this->actingAs($user)->get(route('backup-restore.full.export'))->assertForbidden();
        $this->actingAs($user)->post(route('backup-restore.full.restore'))->assertForbidden();
    }

    public function test_authorized_users_can_export_settings_backup(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        SystemSetting::query()->create([
            'group' => 'pagination',
            'key' => 'default_per_page',
            'value' => '25',
            'encrypted' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('backup-restore.export'))
            ->assertOk();

        $response->assertJsonPath('schema', 'laravel12-starterkit.settings-backup');
        $response->assertJsonPath('sections.system_settings.0.key', 'default_per_page');
    }

    public function test_authorized_users_can_restore_settings_backup(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $payload = [
            'schema' => 'laravel12-starterkit.settings-backup',
            'version' => 1,
            'sections' => [
                'system_settings' => [
                    [
                        'group' => 'pagination',
                        'key' => 'default_per_page',
                        'value' => '50',
                        'encrypted' => false,
                    ],
                ],
                'notification_templates' => [
                    [
                        'key' => 'custom.notice',
                        'name' => 'Custom Notice',
                        'channel' => 'mail',
                        'subject' => 'Subject',
                        'body' => 'Body',
                        'variables' => ['name'],
                        'active' => true,
                    ],
                ],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('settings-backup.json', json_encode($payload));

        $this->actingAs($user)
            ->post(route('backup-restore.restore'), [
                'backup' => $file,
                'restore_system_settings' => true,
                'restore_notification_templates' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('system_settings', [
            'group' => 'pagination',
            'key' => 'default_per_page',
            'value' => '50',
        ]);

        $this->assertDatabaseHas('notification_templates', [
            'key' => 'custom.notice',
            'subject' => 'Subject',
        ]);
    }

    public function test_authorized_users_can_export_full_backup_zip(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('backup-restore.full.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_full_restore_requires_confirmation_text(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $file = UploadedFile::fake()->createWithContent('backup.sql', '-- Laravel 12 Starterkit full database backup');

        $this->actingAs($user)
            ->post(route('backup-restore.full.restore'), [
                'backup' => $file,
                'restore_database' => true,
                'confirmation' => 'WRONG',
            ])
            ->assertSessionHasErrors('confirmation');
    }

    public function test_setting_restore_accepts_json_extension_without_strict_mime(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $payload = [
            'schema' => 'laravel12-starterkit.settings-backup',
            'version' => 1,
            'sections' => [
                'system_settings' => [],
                'notification_templates' => [],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('settings-backup.json', json_encode($payload));

        $this->actingAs($user)
            ->post(route('backup-restore.restore'), [
                'backup' => $file,
                'restore_system_settings' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_setting_restore_explains_unknown_json_schema(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $file = UploadedFile::fake()->createWithContent('settings-backup.json', json_encode([
            'app_name' => 'Demo App',
        ]));

        $this->actingAs($user)
            ->post(route('backup-restore.restore'), [
                'backup' => $file,
                'restore_system_settings' => true,
            ])
            ->assertSessionHasErrors([
                'backup' => 'File JSON valid, tapi bukan Settings Backup aplikasi ini. Field schema tidak ditemukan. Gunakan file dari tombol Download Backup JSON.',
            ]);
    }

    public function test_setting_restore_explains_full_backup_manifest(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $file = UploadedFile::fake()->createWithContent('manifest.json', json_encode([
            'schema' => 'laravel12-starterkit.full-backup',
            'version' => 1,
        ]));

        $this->actingAs($user)
            ->post(route('backup-restore.restore'), [
                'backup' => $file,
                'restore_system_settings' => true,
            ])
            ->assertSessionHasErrors([
                'backup' => 'File JSON ini adalah manifest full backup. Untuk full restore, upload signed full backup .zip pada panel Full Restore.',
            ]);
    }

    public function test_full_restore_rejects_unsigned_sql_dump(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $file = UploadedFile::fake()->createWithContent('backup.sql', '-- Laravel 12 Starterkit full database backup');

        $this->actingAs($user)
            ->post(route('backup-restore.full.restore'), [
                'backup' => $file,
                'restore_database' => true,
                'confirmation' => 'RESTORE FULL BACKUP',
            ])
            ->assertSessionHasErrors([
                'backup' => 'Full restore hanya menerima signed full backup .zip.',
            ]);
    }

    public function test_full_restore_rejects_arbitrary_sql(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('backup-restore.full-restore');
        $file = UploadedFile::fake()->createWithContent('backup.sql', 'DROP TABLE users;');

        $this->actingAs($user)->post(route('backup-restore.full.restore'), [
            'backup' => $file,
            'restore_database' => true,
            'confirmation' => 'RESTORE FULL BACKUP',
        ])->assertSessionHasErrors('backup');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_full_restore_rejects_zip_path_traversal_before_writing_storage(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('backup-restore.full-restore');
        $path = storage_path('framework/testing/malicious-'.uniqid().'.zip');
        File::ensureDirectoryExists(dirname($path));
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('../escape.txt', 'blocked');
        $zip->addFromString('manifest.json', '{}');
        $zip->close();

        try {
            $file = new UploadedFile($path, 'backup.zip', 'application/zip', null, true);
            $this->actingAs($user)->post(route('backup-restore.full.restore'), [
                'backup' => $file,
                'restore_storage_public' => true,
                'confirmation' => 'RESTORE FULL BACKUP',
            ])->assertSessionHasErrors('backup');
            $this->assertFileDoesNotExist(storage_path('framework/testing/escape.txt'));
        } finally {
            File::delete($path);
        }
    }

    public function test_full_restore_rejects_tampered_database_dump(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('backup-restore.full-restore');
        $path = app(FullBackupZipService::class)->create('-- Laravel 12 Starterkit full database backup');
        $zip = new ZipArchive;
        $zip->open($path);
        $zip->addFromString('database.sql', '-- Laravel 12 Starterkit full database backup'.PHP_EOL.'SELECT 1;');
        $zip->close();

        try {
            $file = new UploadedFile($path, 'backup.zip', 'application/zip', null, true);
            $this->actingAs($user)->post(route('backup-restore.full.restore'), [
                'backup' => $file,
                'restore_database' => true,
                'confirmation' => 'RESTORE FULL BACKUP',
            ])->assertSessionHasErrors('backup');
            $this->assertDatabaseHas('users', ['id' => $user->id]);
        } finally {
            File::delete($path);
        }
    }

    public function test_full_restore_rejects_forged_manifest_signature_before_writing(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('backup-restore.full-restore');
        $path = storage_path('framework/testing/forged-signature-'.uniqid().'.zip');
        $sql = '-- Laravel 12 Starterkit full database backup'.PHP_EOL.'SELECT 1;';
        $manifest = [
            'schema' => 'laravel12-starterkit.full-backup',
            'version' => 4,
            'integrity' => [
                'database_sql_sha256' => hash('sha256', $sql),
                'entries_sha256' => ['database.sql' => hash('sha256', $sql)],
            ],
            'authenticity' => [
                'algorithm' => 'hmac-sha256',
                'key_id' => 'test-primary',
                'signature' => str_repeat('0', 64),
            ],
        ];
        File::ensureDirectoryExists(dirname($path));
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('manifest.json', json_encode($manifest));
        $zip->addFromString('database.sql', $sql);
        $zip->close();

        try {
            $file = new UploadedFile($path, 'backup.zip', 'application/zip', null, true);
            $this->actingAs($user)->post(route('backup-restore.full.restore'), [
                'backup' => $file,
                'restore_database' => true,
                'confirmation' => 'RESTORE FULL BACKUP',
            ])->assertSessionHasErrors('backup');
            $this->assertDatabaseHas('users', ['id' => $user->id]);
        } finally {
            File::delete($path);
        }
    }

    public function test_signed_backup_can_be_verified_in_another_environment_with_the_shared_key(): void
    {
        $service = app(FullBackupZipService::class);
        $path = $service->create('-- Laravel 12 Starterkit full database backup');

        try {
            config(['app.env' => 'disaster-recovery', 'app.url' => 'https://recovery.example.test']);
            $file = new UploadedFile($path, 'backup.zip', 'application/zip', null, true);

            $this->assertSame(
                ['database_restored' => false, 'storage_files_restored' => 0],
                $service->restore($file, false, false),
            );
        } finally {
            File::delete($path);
        }
    }

    public function test_full_restore_dry_run_validates_signed_zip_without_writing_files(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('backup-restore.full-restore');
        $storagePath = storage_path('app/public/dry-run/document.pdf');
        File::ensureDirectoryExists(dirname($storagePath));
        File::put($storagePath, 'dry-run-binary');
        $path = app(FullBackupZipService::class)->create('-- Laravel 12 Starterkit full database backup');

        try {
            File::delete($storagePath);
            $file = new UploadedFile($path, 'backup.zip', 'application/zip', null, true);

            $this->actingAs($user)->post(route('backup-restore.full.restore'), [
                'backup' => $file,
                'restore_database' => true,
                'restore_storage_public' => true,
                'dry_run' => true,
                'confirmation' => 'RESTORE FULL BACKUP',
            ])->assertRedirect()
                ->assertSessionHas('success', 'Dry-run full restore valid. Signature, checksum, manifest, dan archive safety lulus tanpa menulis database/storage.');

            $this->assertFileDoesNotExist($storagePath);
            $this->assertAuthenticatedAs($user);
        } finally {
            File::delete($path);
            File::delete($storagePath);
        }
    }

    public function test_signed_backup_is_rejected_in_an_environment_with_a_different_key(): void
    {
        $service = app(FullBackupZipService::class);
        $path = $service->create('-- Laravel 12 Starterkit full database backup');

        try {
            config(['backup.signature_key' => 'different-environment-signature-key-32-bytes']);
            $file = new UploadedFile($path, 'backup.zip', 'application/zip', null, true);

            $this->expectException(ValidationException::class);
            $service->restore($file, false, false);
        } finally {
            File::delete($path);
        }
    }

    public function test_full_backup_export_fails_closed_without_a_signature_key(): void
    {
        config(['backup.signature_key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('BACKUP_SIGNATURE_KEY');

        app(FullBackupZipService::class)->create('-- Laravel 12 Starterkit full database backup');
    }

    public function test_signed_backup_rejects_storage_payload_tampering(): void
    {
        $storagePath = storage_path('app/public/signature-test.txt');
        File::ensureDirectoryExists(dirname($storagePath));
        File::put($storagePath, 'trusted contents');
        $service = app(FullBackupZipService::class);
        $path = $service->create('-- Laravel 12 Starterkit full database backup');
        $zip = new ZipArchive;
        $zip->open($path);
        $zip->addFromString('storage_public/signature-test.txt', 'tampered contents');
        $zip->close();

        try {
            $file = new UploadedFile($path, 'backup.zip', 'application/zip', null, true);
            $this->expectException(ValidationException::class);
            $service->restore($file, false, false);
        } finally {
            File::delete($path);
            File::delete($storagePath);
        }
    }

    public function test_full_backup_round_trip_preserves_public_binary(): void
    {
        $storagePath = storage_path('app/public/objects/checkpoint-c/document.pdf');
        File::ensureDirectoryExists(dirname($storagePath));
        File::put($storagePath, 'public-binary');
        $service = app(FullBackupZipService::class);
        $path = $service->create('-- Laravel 12 Starterkit full database backup');

        try {
            File::delete($storagePath);
            $file = new UploadedFile($path, 'backup.zip', 'application/zip', null, true);

            $summary = $service->restore($file, false, true);

            $this->assertSame('public-binary', File::get($storagePath));
            $this->assertGreaterThanOrEqual(1, $summary['storage_files_restored']);
        } finally {
            File::delete($path);
            File::delete($storagePath);
        }
    }

    public function test_full_restore_is_rate_limited(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('backup-restore.full-restore');

        foreach (range(1, 3) as $attempt) {
            $this->actingAs($user)->post(route('backup-restore.full.restore'), [
                'backup' => UploadedFile::fake()->createWithContent("invalid-{$attempt}.sql", 'invalid'),
                'restore_database' => true,
                'confirmation' => 'WRONG',
            ])->assertRedirect();
        }

        $this->actingAs($user)->post(route('backup-restore.full.restore'), [
            'backup' => UploadedFile::fake()->createWithContent('invalid-4.sql', 'invalid'),
            'restore_database' => true,
            'confirmation' => 'WRONG',
        ])->assertTooManyRequests();
    }
}
