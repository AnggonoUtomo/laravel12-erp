<?php

namespace App\Modules\Console\SystemSettings\Application\DTOs;

final readonly class MaintenanceModeData
{
    public function __construct(
        public bool $enabled,
        public ?string $message,
        public string $pageStyle,
        public ?int $retrySeconds,
        public ?int $refreshSeconds,
        public ?string $secret,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? false),
            message: $data['message'] ?? null,
            pageStyle: $data['page_style'] ?? 'aurora',
            retrySeconds: filled($data['retry_seconds'] ?? null) ? (int) $data['retry_seconds'] : null,
            refreshSeconds: filled($data['refresh_seconds'] ?? null) ? (int) $data['refresh_seconds'] : null,
            secret: $data['secret'] ?? null,
        );
    }
}
