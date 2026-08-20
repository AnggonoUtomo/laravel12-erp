<?php

namespace App\Modules\Console\SystemSettings\Services;

use App\Modules\Console\AuditLogs\Services\AuditLogService;
use App\Modules\Console\SystemSettings\DTO\BrandingSettingData;
use App\Modules\Console\SystemSettings\DTO\EmailSettingData;
use App\Modules\Console\SystemSettings\DTO\LocalizationSettingData;
use App\Modules\Console\SystemSettings\DTO\MaintenanceModeData;
use App\Modules\Console\SystemSettings\DTO\PaginationSettingData;
use App\Modules\Console\SystemSettings\DTO\PasswordPolicyData;
use App\Modules\Console\SystemSettings\DTO\SecurityPolicyData;
use App\Modules\Console\SystemSettings\Mail\SmtpTestMail;
use App\Modules\Console\SystemSettings\Models\SystemSetting;
use App\Modules\Console\SystemSettings\Transactions\SystemSettingTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Throwable;

class SystemSettingService
{
    private const EMAIL_GROUP = 'email';

    private const BRANDING_GROUP = 'branding';

    private const LOCALIZATION_GROUP = 'localization';

    private const PAGINATION_GROUP = 'pagination';

    private const SECURITY_POLICY_GROUP = 'security_policy';

    private const PASSWORD_POLICY_GROUP = 'password_policy';

    private const MAINTENANCE_MODE_GROUP = 'maintenance_mode';

    private const BRANDING_ASSET_KEY = 'assets';

    private const BRANDING_LOGO_COLLECTION = 'branding_logo';

    private const BRANDING_FAVICON_COLLECTION = 'branding_favicon';

    /**
     * @var array<string, mixed>
     */
    private const EMAIL_DEFAULTS = [
        'enabled' => false,
        'mailer' => 'smtp',
        'host' => null,
        'port' => 587,
        'username' => null,
        'password' => null,
        'encryption' => 'tls',
        'from_address' => 'hello@example.com',
        'from_name' => 'Laravel',
        'send_credentials_on_create' => true,
        'send_credentials_on_password_update' => true,
        'credential_subject' => 'Aktivasi akun dan atur password',
        'credential_intro' => 'Akun kamu sudah dibuat. Gunakan tautan berikut untuk aktivasi dan mengatur password.',
    ];

    /**
     * @var array<string, mixed>
     */
    private const BRANDING_DEFAULTS = [
        'app_name' => null,
        'logo_url' => null,
        'favicon_url' => null,
    ];

    /**
     * @var array<string, mixed>
     */
    private const LOCALIZATION_DEFAULTS = [
        'timezone' => null,
        'date_format' => 'd M Y',
        'time_format' => 'H:i',
    ];

    /**
     * @var array<string, mixed>
     */
    private const PAGINATION_DEFAULTS = [
        'default_per_page' => 10,
        'per_page_options' => [5, 10, 15, 25, 50],
    ];

    /**
     * @var array<string, mixed>
     */
    private const SECURITY_POLICY_DEFAULTS = [
        'require_email_verification' => false,
        'audit_sensitive_actions' => true,
        'single_session_per_user' => false,
        'allow_account_deletion' => true,
        'session_lifetime_minutes' => 120,
        'login_max_attempts' => 5,
        'login_decay_minutes' => 1,
        'password_confirmation_timeout_seconds' => 10800,
    ];

    /**
     * @var array<string, mixed>
     */
    private const PASSWORD_POLICY_DEFAULTS = [
        'min_length' => 8,
        'require_uppercase' => false,
        'require_lowercase' => false,
        'require_numbers' => false,
        'require_symbols' => false,
        'uncompromised' => false,
        'expiry_days' => 0,
        'history_count' => 0,
    ];

    /**
     * @var array<string, mixed>
     */
    private const MAINTENANCE_MODE_DEFAULTS = [
        'enabled' => false,
        'message' => 'Aplikasi sedang dalam mode maintenance. Silakan coba lagi beberapa saat lagi.',
        'page_style' => 'aurora',
        'retry_seconds' => 300,
        'refresh_seconds' => null,
        'secret' => null,
    ];

    /**
     * @var array<int, string>
     */
    private const ENCRYPTED_KEYS = [
        'password',
        'secret',
    ];

    public function __construct(
        private readonly SystemSettingTransaction $transaction,
        private readonly AuditLogService $audit,
        private readonly SystemHealthService $systemHealthService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function emailSettings(bool $includeSecret = false): array
    {
        $settings = self::EMAIL_DEFAULTS;
        $stored = $this->storedEmailSettings();

        foreach ($stored as $key => $value) {
            $settings[$key] = $value;
        }

        $settings['password_configured'] = filled($settings['password']);

        if (! $includeSecret) {
            $settings['password'] = null;
        }

        return $settings;
    }

    public function updateEmailSettings(EmailSettingData $data): void
    {
        $oldValues = $this->safeEmailSettingsForAudit();

        $this->transaction->run(function () use ($data) {
            $payload = [
                'enabled' => $data->enabled,
                'mailer' => $data->mailer,
                'host' => $data->host,
                'port' => $data->port,
                'username' => $data->username,
                'encryption' => $data->encryption,
                'from_address' => $data->fromAddress,
                'from_name' => $data->fromName,
                'send_credentials_on_create' => $data->sendCredentialsOnCreate,
                'send_credentials_on_password_update' => $data->sendCredentialsOnPasswordUpdate,
                'credential_subject' => $data->credentialSubject,
                'credential_intro' => $data->credentialIntro,
            ];

            if ($data->password !== null && $data->password !== '') {
                $payload['password'] = $data->password;
            }

            foreach ($payload as $key => $value) {
                $this->put(self::EMAIL_GROUP, $key, $value, in_array($key, self::ENCRYPTED_KEYS, true));
            }
        });

        $this->applyMailSettings();

        $this->audit->record(
            module: 'system-settings',
            event: 'email.updated',
            description: 'Updated email configuration',
            oldValues: $oldValues,
            newValues: $this->safeEmailSettingsForAudit(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function brandingSettings(): array
    {
        $settings = self::BRANDING_DEFAULTS;
        $settings['app_name'] = config('app.name', 'Laravel');

        if (! $this->settingsTableExists()) {
            return $settings;
        }

        try {
            $storedName = SystemSetting::query()
                ->where('group', self::BRANDING_GROUP)
                ->where('key', 'app_name')
                ->value('value');

            if (filled($storedName)) {
                $settings['app_name'] = $storedName;
            }

            if ($this->mediaTableExists()) {
                $assetSetting = $this->brandingAssetSetting();

                $settings['logo_url'] = $assetSetting?->getFirstMediaUrl(self::BRANDING_LOGO_COLLECTION) ?: null;
                $settings['favicon_url'] = $assetSetting?->getFirstMediaUrl(self::BRANDING_FAVICON_COLLECTION) ?: null;
            }
        } catch (Throwable) {
            return $settings;
        }

        return $settings;
    }

    public function updateBrandingSettings(BrandingSettingData $data): void
    {
        $oldValues = $this->brandingSettings();

        $this->transaction->run(function () use ($data) {
            $this->put(self::BRANDING_GROUP, 'app_name', $data->appName);

            $assetSetting = $this->brandingAssetSetting(create: true);

            if ($data->removeLogo) {
                $assetSetting->clearMediaCollection(self::BRANDING_LOGO_COLLECTION);
            }

            if ($data->removeFavicon) {
                $assetSetting->clearMediaCollection(self::BRANDING_FAVICON_COLLECTION);
            }

            if ($data->logo) {
                $assetSetting
                    ->addMedia($data->logo)
                    ->toMediaCollection(self::BRANDING_LOGO_COLLECTION);
            }

            if ($data->favicon) {
                $assetSetting
                    ->addMedia($data->favicon)
                    ->toMediaCollection(self::BRANDING_FAVICON_COLLECTION);
            }
        });

        $this->audit->record(
            module: 'system-settings',
            event: 'branding.updated',
            description: 'Updated application branding',
            oldValues: $oldValues,
            newValues: $this->brandingSettings(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function localizationSettings(): array
    {
        $settings = self::LOCALIZATION_DEFAULTS;
        $settings['timezone'] = config('app.timezone', 'UTC');

        if (! $this->settingsTableExists()) {
            return $this->withDateTimePreview($settings);
        }

        try {
            $stored = SystemSetting::query()
                ->where('group', self::LOCALIZATION_GROUP)
                ->get()
                ->mapWithKeys(fn (SystemSetting $setting) => [$setting->key => $this->castValue($setting)])
                ->all();

            foreach ($stored as $key => $value) {
                $settings[$key] = $value;
            }
        } catch (Throwable) {
            return $this->withDateTimePreview($settings);
        }

        return $this->withDateTimePreview($settings);
    }

    public function updateLocalizationSettings(LocalizationSettingData $data): void
    {
        $oldValues = $this->localizationSettings();

        $this->transaction->run(function () use ($data) {
            $this->put(self::LOCALIZATION_GROUP, 'timezone', $data->timezone);
            $this->put(self::LOCALIZATION_GROUP, 'date_format', $data->dateFormat);
            $this->put(self::LOCALIZATION_GROUP, 'time_format', $data->timeFormat);
        });

        $this->applyLocalizationSettings();

        $this->audit->record(
            module: 'system-settings',
            event: 'localization.updated',
            description: 'Updated timezone and date format',
            oldValues: $oldValues,
            newValues: $this->localizationSettings(),
        );
    }

    public function applyLocalizationSettings(): void
    {
        $settings = $this->localizationSettings();

        config(['app.timezone' => $settings['timezone']]);
        date_default_timezone_set($settings['timezone']);
    }

    /**
     * @return array<string, mixed>
     */
    public function paginationSettings(): array
    {
        $settings = self::PAGINATION_DEFAULTS;

        if (! $this->settingsTableExists()) {
            return $settings;
        }

        try {
            $stored = SystemSetting::query()
                ->where('group', self::PAGINATION_GROUP)
                ->get()
                ->mapWithKeys(fn (SystemSetting $setting) => [$setting->key => $this->castValue($setting)])
                ->all();

            foreach ($stored as $key => $value) {
                $settings[$key] = $value;
            }
        } catch (Throwable) {
            return self::PAGINATION_DEFAULTS;
        }

        $settings['per_page_options'] = collect($settings['per_page_options'])
            ->map(fn ($option) => (int) $option)
            ->filter(fn (int $option) => $option > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($settings['per_page_options'] === []) {
            $settings['per_page_options'] = self::PAGINATION_DEFAULTS['per_page_options'];
        }

        $settings['default_per_page'] = (int) $settings['default_per_page'];

        if (! in_array($settings['default_per_page'], $settings['per_page_options'], true)) {
            $settings['default_per_page'] = $settings['per_page_options'][0];
        }

        return $settings;
    }

    public function updatePaginationSettings(PaginationSettingData $data): void
    {
        $oldValues = $this->paginationSettings();

        $this->transaction->run(function () use ($data) {
            $this->put(self::PAGINATION_GROUP, 'default_per_page', $data->defaultPerPage);
            $this->put(self::PAGINATION_GROUP, 'per_page_options', implode(',', $data->perPageOptions));
        });

        $this->audit->record(
            module: 'system-settings',
            event: 'pagination.updated',
            description: 'Updated default pagination settings',
            oldValues: $oldValues,
            newValues: $this->paginationSettings(),
        );
    }

    public function resolvePerPage(Request $request): int
    {
        $settings = $this->paginationSettings();
        $default = (int) $settings['default_per_page'];
        $options = $settings['per_page_options'];
        $perPage = $request->integer('per_page', $default);

        return in_array($perPage, $options, true) ? $perPage : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function securityPolicySettings(): array
    {
        return $this->storedSettings(self::SECURITY_POLICY_GROUP, self::SECURITY_POLICY_DEFAULTS);
    }

    public function updateSecurityPolicy(SecurityPolicyData $data): void
    {
        $oldValues = $this->securityPolicySettings();

        $this->transaction->run(function () use ($data) {
            $this->put(self::SECURITY_POLICY_GROUP, 'require_email_verification', $data->requireEmailVerification);
            $this->put(self::SECURITY_POLICY_GROUP, 'audit_sensitive_actions', $data->auditSensitiveActions);
            $this->put(self::SECURITY_POLICY_GROUP, 'single_session_per_user', $data->singleSessionPerUser);
            $this->put(self::SECURITY_POLICY_GROUP, 'allow_account_deletion', $data->allowAccountDeletion);
            $this->put(self::SECURITY_POLICY_GROUP, 'session_lifetime_minutes', $data->sessionLifetimeMinutes);
            $this->put(self::SECURITY_POLICY_GROUP, 'login_max_attempts', $data->loginMaxAttempts);
            $this->put(self::SECURITY_POLICY_GROUP, 'login_decay_minutes', $data->loginDecayMinutes);
            $this->put(self::SECURITY_POLICY_GROUP, 'password_confirmation_timeout_seconds', $data->passwordConfirmationTimeoutSeconds);
        });

        $this->audit->record(
            module: 'system-settings',
            event: 'security_policy.updated',
            description: 'Updated security policy',
            oldValues: $oldValues,
            newValues: $this->securityPolicySettings(),
        );
    }

    public function applySecurityPolicy(): void
    {
        $settings = $this->securityPolicySettings();

        config([
            'session.lifetime' => $settings['session_lifetime_minutes'],
            'auth.password_timeout' => $settings['password_confirmation_timeout_seconds'],
        ]);
    }

    public function accountDeletionEnabled(): bool
    {
        return (bool) $this->securityPolicySettings()['allow_account_deletion'];
    }

    /**
     * @return array<string, mixed>
     */
    public function passwordPolicySettings(): array
    {
        return $this->storedSettings(self::PASSWORD_POLICY_GROUP, self::PASSWORD_POLICY_DEFAULTS);
    }

    public function updatePasswordPolicy(PasswordPolicyData $data): void
    {
        $oldValues = $this->passwordPolicySettings();

        $this->transaction->run(function () use ($data) {
            $this->put(self::PASSWORD_POLICY_GROUP, 'min_length', $data->minLength);
            $this->put(self::PASSWORD_POLICY_GROUP, 'require_uppercase', $data->requireUppercase);
            $this->put(self::PASSWORD_POLICY_GROUP, 'require_lowercase', $data->requireLowercase);
            $this->put(self::PASSWORD_POLICY_GROUP, 'require_numbers', $data->requireNumbers);
            $this->put(self::PASSWORD_POLICY_GROUP, 'require_symbols', $data->requireSymbols);
            $this->put(self::PASSWORD_POLICY_GROUP, 'uncompromised', $data->uncompromised);
            $this->put(self::PASSWORD_POLICY_GROUP, 'expiry_days', $data->expiryDays);
            $this->put(self::PASSWORD_POLICY_GROUP, 'history_count', $data->historyCount);
        });

        $this->audit->record(
            module: 'system-settings',
            event: 'password_policy.updated',
            description: 'Updated password policy',
            oldValues: $oldValues,
            newValues: $this->passwordPolicySettings(),
        );
    }

    public function passwordRule(): Password
    {
        $settings = $this->passwordPolicySettings();
        $rule = Password::min((int) $settings['min_length']);

        if ($settings['require_uppercase']) {
            $rule->mixedCase();
        }

        if ($settings['require_numbers']) {
            $rule->numbers();
        }

        if ($settings['require_symbols']) {
            $rule->symbols();
        }

        if ($settings['uncompromised']) {
            $rule->uncompromised();
        }

        return $rule;
    }

    /**
     * @return array<int, mixed>
     */
    public function passwordRules(bool $nullable = false): array
    {
        $settings = $this->passwordPolicySettings();
        $rules = [$nullable ? 'nullable' : 'required', 'confirmed', $this->passwordRule()];

        if ($settings['require_lowercase'] && ! $settings['require_uppercase']) {
            $rules[] = function (string $attribute, mixed $value, \Closure $fail) {
                if (! preg_match('/[a-z]/', (string) $value)) {
                    $fail('Password harus memiliki minimal satu huruf kecil.');
                }
            };
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function maintenanceModeSettings(bool $includeSecret = false): array
    {
        $settings = $this->storedSettings(self::MAINTENANCE_MODE_GROUP, self::MAINTENANCE_MODE_DEFAULTS);
        $settings['active'] = app()->isDownForMaintenance();
        $settings['secret_configured'] = filled($settings['secret']);
        $settings['bypass_url'] = $includeSecret && filled($settings['secret']) ? url($settings['secret']) : null;

        if (! $includeSecret) {
            $settings['secret'] = null;
        }

        return $settings;
    }

    /** @return array<string, mixed> */
    public function systemHealth(): array
    {
        return $this->systemHealthService->systemHealth();
    }

    /** @return array<string, mixed> */
    public function environmentInfo(): array
    {
        return $this->systemHealthService->environmentInfo();
    }

    public function updateMaintenanceMode(MaintenanceModeData $data): void
    {
        $oldValues = $this->maintenanceModeSettings();

        $this->transaction->run(function () use ($data) {
            $this->put(self::MAINTENANCE_MODE_GROUP, 'enabled', $data->enabled);
            $this->put(self::MAINTENANCE_MODE_GROUP, 'message', $data->message);
            $this->put(self::MAINTENANCE_MODE_GROUP, 'page_style', $data->pageStyle);
            $this->put(self::MAINTENANCE_MODE_GROUP, 'retry_seconds', $data->retrySeconds);
            $this->put(self::MAINTENANCE_MODE_GROUP, 'refresh_seconds', $data->refreshSeconds);

            if (filled($data->secret)) {
                $this->put(self::MAINTENANCE_MODE_GROUP, 'secret', $data->secret, true);
            }
        });

        $this->applyMaintenanceMode();

        $this->audit->record(
            module: 'system-settings',
            event: 'maintenance_mode.updated',
            description: 'Updated maintenance mode',
            oldValues: $oldValues,
            newValues: $this->maintenanceModeSettings(),
        );
    }

    public function applyMaintenanceMode(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $settings = $this->maintenanceModeSettings(includeSecret: true);

        if (! $settings['enabled']) {
            Artisan::call('up');

            return;
        }

        $parameters = [
            '--render' => 'errors::503',
            '--retry' => $settings['retry_seconds'] ?: null,
        ];

        if (filled($settings['refresh_seconds'])) {
            $parameters['--refresh'] = $settings['refresh_seconds'];
        }

        if (filled($settings['secret'])) {
            $parameters['--secret'] = $settings['secret'];
        }

        Artisan::call('down', array_filter($parameters, fn (mixed $value) => $value !== null));
    }

    /**
     * @return array<string, mixed>
     */
    public function applyMailSettings(bool $force = false): void
    {
        if (! $this->settingsTableExists()) {
            return;
        }

        $settings = $this->emailSettings(includeSecret: true);

        if (! $force && ! $settings['enabled']) {
            return;
        }

        $mailer = $settings['mailer'] ?: 'smtp';

        config([
            'mail.default' => $mailer,
            'mail.from.address' => $settings['from_address'],
            'mail.from.name' => $settings['from_name'],
        ]);

        if ($mailer !== 'smtp') {
            return;
        }

        config([
            'mail.mailers.smtp.host' => $settings['host'],
            'mail.mailers.smtp.port' => $settings['port'],
            'mail.mailers.smtp.username' => $settings['username'],
            'mail.mailers.smtp.password' => $settings['password'],
            'mail.mailers.smtp.encryption' => $settings['encryption'],
        ]);
    }

    public function sendTestEmail(string $recipient): void
    {
        $this->applyMailSettings(force: true);

        try {
            Mail::to($recipient)->send(new SmtpTestMail(now()->format('d M Y H:i:s')));

            $this->audit->record(
                module: 'system-settings',
                event: 'email.test_succeeded',
                description: "Sent SMTP test email to {$recipient}",
                newValues: [
                    'recipient' => $recipient,
                    'mailer' => config('mail.default'),
                ],
            );
        } catch (Throwable $exception) {
            $this->audit->record(
                module: 'system-settings',
                event: 'email.test_failed',
                description: "Failed SMTP test email to {$recipient}",
                newValues: [
                    'recipient' => $recipient,
                    'mailer' => config('mail.default'),
                    'error' => $exception->getMessage(),
                ],
            );

            throw $exception;
        }
    }

    public function shouldSendCredentialsOnCreate(): bool
    {
        $settings = $this->emailSettings();

        return (bool) $settings['enabled'] && (bool) $settings['send_credentials_on_create'];
    }

    public function shouldSendCredentialsOnPasswordUpdate(): bool
    {
        $settings = $this->emailSettings();

        return (bool) $settings['enabled'] && (bool) $settings['send_credentials_on_password_update'];
    }

    /**
     * @return array<string, mixed>
     */
    public function credentialTemplate(): array
    {
        $settings = $this->emailSettings();

        return [
            'subject' => $settings['credential_subject'],
            'intro' => $settings['credential_intro'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storedEmailSettings(): array
    {
        if (! $this->settingsTableExists()) {
            return [];
        }

        return SystemSetting::query()
            ->where('group', self::EMAIL_GROUP)
            ->get()
            ->mapWithKeys(fn (SystemSetting $setting) => [$setting->key => $this->castValue($setting)])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function storedSettings(string $group, array $defaults): array
    {
        $settings = $defaults;

        if (! $this->settingsTableExists()) {
            return $settings;
        }

        try {
            $stored = SystemSetting::query()
                ->where('group', $group)
                ->get()
                ->mapWithKeys(fn (SystemSetting $setting) => [$setting->key => $this->castValue($setting)])
                ->all();

            foreach ($stored as $key => $value) {
                $settings[$key] = $value;
            }
        } catch (Throwable) {
            return $defaults;
        }

        return $settings;
    }

    private function put(string $group, string $key, mixed $value, bool $encrypted = false): void
    {
        SystemSetting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'value' => $encrypted && filled($value) ? Crypt::encryptString((string) $value) : $this->serializeValue($value),
                'encrypted' => $encrypted,
            ],
        );
    }

    private function brandingAssetSetting(bool $create = false): ?SystemSetting
    {
        if ($create) {
            return SystemSetting::query()->firstOrCreate(
                ['group' => self::BRANDING_GROUP, 'key' => self::BRANDING_ASSET_KEY],
                ['value' => null, 'encrypted' => false],
            );
        }

        return SystemSetting::query()
            ->where('group', self::BRANDING_GROUP)
            ->where('key', self::BRANDING_ASSET_KEY)
            ->first();
    }

    private function castValue(SystemSetting $setting): mixed
    {
        if ($setting->encrypted) {
            if (! filled($setting->value)) {
                return null;
            }

            try {
                return Crypt::decryptString($setting->value);
            } catch (Throwable) {
                return null;
            }
        }

        return match (true) {
            in_array($setting->key, ['enabled', 'send_credentials_on_create', 'send_credentials_on_password_update', 'require_email_verification', 'audit_sensitive_actions', 'single_session_per_user', 'allow_account_deletion', 'require_uppercase', 'require_lowercase', 'require_numbers', 'require_symbols', 'uncompromised'], true) => filter_var($setting->value, FILTER_VALIDATE_BOOL),
            in_array($setting->key, ['default_per_page', 'session_lifetime_minutes', 'login_max_attempts', 'login_decay_minutes', 'password_confirmation_timeout_seconds', 'min_length', 'expiry_days', 'history_count', 'retry_seconds', 'refresh_seconds'], true) => filled($setting->value) ? (int) $setting->value : null,
            $setting->key === 'per_page_options' => collect(explode(',', (string) $setting->value))
                ->map(fn (string $option) => (int) trim($option))
                ->filter(fn (int $option) => $option > 0)
                ->values()
                ->all(),
            $setting->key === 'port' => filled($setting->value) ? (int) $setting->value : null,
            default => $setting->value,
        };
    }

    private function serializeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return Str::of((string) $value)->trim()->toString();
    }

    private function settingsTableExists(): bool
    {
        try {
            return Schema::hasTable('system_settings');
        } catch (Throwable) {
            return false;
        }
    }

    private function mediaTableExists(): bool
    {
        try {
            return Schema::hasTable('media');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function withDateTimePreview(array $settings): array
    {
        $now = now($settings['timezone']);

        $settings['datetime_format'] = "{$settings['date_format']} {$settings['time_format']}";
        $settings['preview_date'] = $now->format($settings['date_format']);
        $settings['preview_time'] = $now->format($settings['time_format']);
        $settings['preview_datetime'] = $now->format($settings['datetime_format']);

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    private function safeEmailSettingsForAudit(): array
    {
        $settings = $this->emailSettings();

        unset($settings['password']);

        return $settings;
    }
}
