<?php

namespace App\Modules\Console\SystemSettings\Application\DTOs;

final readonly class EmailSettingData
{
    public function __construct(
        public bool $enabled,
        public string $mailer,
        public ?string $host,
        public ?int $port,
        public ?string $username,
        public ?string $password,
        public ?string $encryption,
        public string $fromAddress,
        public string $fromName,
        public bool $sendCredentialsOnCreate,
        public bool $sendCredentialsOnPasswordUpdate,
        public ?string $credentialSubject,
        public ?string $credentialIntro,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? false),
            mailer: $data['mailer'] ?? 'smtp',
            host: $data['host'] ?? null,
            port: isset($data['port']) ? (int) $data['port'] : null,
            username: $data['username'] ?? null,
            password: $data['password'] ?? null,
            encryption: ($data['encryption'] ?? null) ?: null,
            fromAddress: $data['from_address'],
            fromName: $data['from_name'],
            sendCredentialsOnCreate: (bool) ($data['send_credentials_on_create'] ?? true),
            sendCredentialsOnPasswordUpdate: (bool) ($data['send_credentials_on_password_update'] ?? true),
            credentialSubject: $data['credential_subject'] ?? null,
            credentialIntro: $data['credential_intro'] ?? null,
        );
    }
}
