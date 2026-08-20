<?php

namespace App\Modules\Console\NotificationTemplates\Services;

use App\Modules\Console\AuditLogs\Application\Services\AuditLogService;
use App\Modules\Console\NotificationTemplates\Models\NotificationTemplate;
use Illuminate\Support\Collection;

class NotificationTemplateService
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const DEFAULT_TEMPLATES = [
        'user.activation' => [
            'name' => 'Aktivasi User',
            'channel' => 'mail',
            'subject' => 'Aktivasi akun dan atur password',
            'body' => "Halo {{ name }},\n\nAkun kamu sudah dibuat. Gunakan tombol aktivasi untuk mengatur password dan mulai masuk ke aplikasi.\n\nAbaikan email ini jika kamu tidak merasa perlu mengaktifkan akun.",
            'variables' => ['name', 'email', 'action_url', 'app_name'],
            'active' => true,
        ],
        'user.credential' => [
            'name' => 'Credential User',
            'channel' => 'mail',
            'subject' => 'Informasi akses akun',
            'body' => "Halo {{ name }},\n\nBerikut informasi akses akun kamu.\n\nEmail: {{ email }}\nPassword: {{ password }}\n\nDemi keamanan, segera ubah kata sandi setelah berhasil login.",
            'variables' => ['name', 'email', 'password', 'login_url', 'app_name'],
            'active' => true,
        ],
        'smtp.test' => [
            'name' => 'Test SMTP',
            'channel' => 'mail',
            'subject' => 'Test SMTP berhasil',
            'body' => "Halo,\n\nEmail ini dikirim untuk memastikan konfigurasi SMTP berjalan dengan baik.\n\nWaktu pengujian: {{ tested_at }}",
            'variables' => ['tested_at', 'app_name'],
            'active' => true,
        ],
    ];

    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    /**
     * @return Collection<int, NotificationTemplate>
     */
    public function templates(): Collection
    {
        $this->ensureDefaults();

        return NotificationTemplate::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function findByKey(string $key): array
    {
        $this->ensureDefaults();

        $template = NotificationTemplate::query()->where('key', $key)->first();
        $defaults = self::DEFAULT_TEMPLATES[$key] ?? [
            'name' => str($key)->headline()->toString(),
            'channel' => 'mail',
            'subject' => null,
            'body' => null,
            'variables' => [],
            'active' => true,
        ];

        return [
            'key' => $key,
            'name' => $template?->name ?? $defaults['name'],
            'channel' => $template?->channel ?? $defaults['channel'],
            'subject' => $template?->subject ?? $defaults['subject'],
            'body' => $template?->body ?? $defaults['body'],
            'variables' => $template?->variables ?? $defaults['variables'],
            'active' => $template?->active ?? $defaults['active'],
        ];
    }

    /**
     * @param  array{subject?: string|null, body?: string|null, active?: bool}  $data
     */
    public function update(NotificationTemplate $template, array $data): NotificationTemplate
    {
        $oldValues = $template->only(['subject', 'body', 'active']);

        $template->update([
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
            'active' => (bool) ($data['active'] ?? false),
        ]);

        $this->audit->record(
            module: 'notification-templates',
            event: 'template.updated',
            auditable: $template,
            description: "Updated notification template {$template->key}",
            oldValues: $oldValues,
            newValues: $template->only(['subject', 'body', 'active']),
        );

        return $template;
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function render(string $key, array $variables = []): array
    {
        $template = $this->findByKey($key);

        if (! $template['active']) {
            return $template;
        }

        $template['subject'] = $this->replaceVariables((string) $template['subject'], $variables);
        $template['body'] = $this->replaceVariables((string) $template['body'], $variables);

        return $template;
    }

    public function ensureDefaults(): void
    {
        foreach (self::DEFAULT_TEMPLATES as $key => $template) {
            NotificationTemplate::query()->firstOrCreate(
                ['key' => $key],
                [
                    'name' => $template['name'],
                    'channel' => $template['channel'],
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'variables' => $template['variables'],
                    'active' => $template['active'],
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{ '.$key.' }}', (string) $value, $content);
            $content = str_replace('{{'.$key.'}}', (string) $value, $content);
        }

        return $content;
    }
}
