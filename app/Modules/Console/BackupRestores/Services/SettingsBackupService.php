<?php

namespace App\Modules\Console\BackupRestores\Services;

use App\Modules\Console\AuditLogs\Application\Services\AuditLogService;
use App\Modules\Console\NotificationTemplates\Models\NotificationTemplate;
use App\Modules\Console\SystemSettings\Infrastructure\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use JsonException;

class SettingsBackupService
{
    private const VERSION = 1;

    public function __construct(private readonly AuditLogService $audit) {}

    /** @return array<string, mixed> */
    public function overview(): array
    {
        $storagePublicPath = storage_path('app/public');

        return [
            'system_settings' => SystemSetting::query()->count(),
            'encrypted_settings' => SystemSetting::query()->where('encrypted', true)->count(),
            'notification_templates' => NotificationTemplate::query()->count(),
            'database_connection' => config('database.default'),
            'database_name' => config('database.connections.'.config('database.default').'.database'),
            'storage_public_exists' => File::isDirectory($storagePublicPath),
            'storage_public_size' => File::isDirectory($storagePublicPath)
                ? collect(File::allFiles($storagePublicPath))->sum(fn ($file) => $file->getSize())
                : 0,
            'last_ready_at' => now()->format('d M Y H:i:s'),
            'included_sections' => ['system_settings', 'notification_templates'],
            'excluded_sections' => ['users', 'roles_permissions', 'audit_logs', 'login_activities', 'media_files'],
        ];
    }

    /** @return array<string, mixed> */
    public function export(): array
    {
        $payload = [
            'schema' => 'laravel12-starterkit.settings-backup',
            'version' => self::VERSION,
            'exported_at' => now()->toISOString(),
            'app' => ['name' => config('app.name'), 'url' => config('app.url'), 'environment' => config('app.env')],
            'sections' => [
                'system_settings' => SystemSetting::query()->orderBy('group')->orderBy('key')
                    ->get(['group', 'key', 'value', 'encrypted'])
                    ->map(fn (SystemSetting $setting) => [
                        'group' => $setting->group, 'key' => $setting->key,
                        'value' => $setting->value, 'encrypted' => $setting->encrypted,
                    ])->values()->all(),
                'notification_templates' => NotificationTemplate::query()->orderBy('key')
                    ->get(['key', 'name', 'channel', 'subject', 'body', 'variables', 'active'])
                    ->map(fn (NotificationTemplate $template) => [
                        'key' => $template->key, 'name' => $template->name, 'channel' => $template->channel,
                        'subject' => $template->subject, 'body' => $template->body,
                        'variables' => $template->variables, 'active' => $template->active,
                    ])->values()->all(),
            ],
        ];

        $this->audit->record(
            module: 'backup-restore', event: 'settings.exported', description: 'Exported settings backup',
            newValues: [
                'system_settings' => count($payload['sections']['system_settings']),
                'notification_templates' => count($payload['sections']['notification_templates']),
            ],
        );

        return $payload;
    }

    /** @return array<string, int> */
    public function restore(UploadedFile $file, bool $restoreSystemSettings, bool $restoreNotificationTemplates): array
    {
        if (! $restoreSystemSettings && ! $restoreNotificationTemplates) {
            throw ValidationException::withMessages(['backup' => 'Pilih minimal satu section yang ingin direstore.']);
        }

        $payload = $this->payload($file);
        if ((int) ($payload['version'] ?? 0) !== self::VERSION) {
            throw ValidationException::withMessages(['backup' => 'Versi backup tidak kompatibel dengan aplikasi ini.']);
        }

        $sections = $payload['sections'];
        $summary = ['system_settings' => 0, 'notification_templates' => 0];
        DB::transaction(function () use ($sections, $restoreSystemSettings, $restoreNotificationTemplates, &$summary) {
            if ($restoreSystemSettings) {
                foreach (($sections['system_settings'] ?? []) as $setting) {
                    if (! is_array($setting) || blank($setting['group'] ?? null) || blank($setting['key'] ?? null)) {
                        continue;
                    }
                    SystemSetting::query()->updateOrCreate(
                        ['group' => $setting['group'], 'key' => $setting['key']],
                        ['value' => $setting['value'] ?? null, 'encrypted' => (bool) ($setting['encrypted'] ?? false)],
                    );
                    $summary['system_settings']++;
                }
            }

            if ($restoreNotificationTemplates) {
                foreach (($sections['notification_templates'] ?? []) as $template) {
                    if (! is_array($template) || blank($template['key'] ?? null)) {
                        continue;
                    }
                    NotificationTemplate::query()->updateOrCreate(['key' => $template['key']], [
                        'name' => $template['name'] ?? str($template['key'])->headline()->toString(),
                        'channel' => $template['channel'] ?? 'mail', 'subject' => $template['subject'] ?? null,
                        'body' => $template['body'] ?? null, 'variables' => $template['variables'] ?? [],
                        'active' => (bool) ($template['active'] ?? true),
                    ]);
                    $summary['notification_templates']++;
                }
            }
        });

        $this->audit->record(module: 'backup-restore', event: 'settings.restored', description: 'Restored settings backup', newValues: $summary);

        return $summary;
    }

    /** @return array<string, mixed> */
    private function payload(UploadedFile $file): array
    {
        try {
            $payload = json_decode($file->get(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['backup' => 'Isi file bukan JSON valid. Pastikan file berasal dari tombol Download Backup JSON.']);
        }

        if (! is_array($payload)) {
            throw ValidationException::withMessages(['backup' => 'Root JSON harus berupa object backup setting.']);
        }

        $schema = $payload['schema'] ?? null;
        if ($schema === 'laravel12-starterkit.full-backup') {
            throw ValidationException::withMessages(['backup' => 'File JSON ini adalah manifest full backup. Untuk full restore, upload signed full backup .zip pada panel Full Restore.']);
        }
        if ($schema !== 'laravel12-starterkit.settings-backup') {
            $message = is_string($schema) && $schema !== '' ? "Schema yang terbaca: {$schema}." : 'Field schema tidak ditemukan.';
            throw ValidationException::withMessages(['backup' => "File JSON valid, tapi bukan Settings Backup aplikasi ini. {$message} Gunakan file dari tombol Download Backup JSON."]);
        }
        if (! is_array($payload['sections'] ?? null)) {
            throw ValidationException::withMessages(['backup' => 'Struktur sections tidak ditemukan di file backup setting.']);
        }

        return $payload;
    }
}
