<?php

namespace App\Modules\Console\SystemSettings\Application\DTOs;

final readonly class PasswordPolicyData
{
    public function __construct(
        public int $minLength,
        public bool $requireUppercase,
        public bool $requireLowercase,
        public bool $requireNumbers,
        public bool $requireSymbols,
        public bool $uncompromised,
        public int $expiryDays,
        public int $historyCount,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            minLength: (int) $data['min_length'],
            requireUppercase: (bool) ($data['require_uppercase'] ?? false),
            requireLowercase: (bool) ($data['require_lowercase'] ?? false),
            requireNumbers: (bool) ($data['require_numbers'] ?? false),
            requireSymbols: (bool) ($data['require_symbols'] ?? false),
            uncompromised: (bool) ($data['uncompromised'] ?? false),
            expiryDays: (int) ($data['expiry_days'] ?? 0),
            historyCount: (int) ($data['history_count'] ?? 0),
        );
    }
}
