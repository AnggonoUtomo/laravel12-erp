<?php

namespace App\Modules\Console\SystemSettings\Application\DTOs;

final readonly class SecurityPolicyData
{
    public function __construct(
        public bool $requireEmailVerification,
        public bool $auditSensitiveActions,
        public bool $singleSessionPerUser,
        public bool $allowAccountDeletion,
        public int $sessionLifetimeMinutes,
        public int $loginMaxAttempts,
        public int $loginDecayMinutes,
        public int $passwordConfirmationTimeoutSeconds,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            requireEmailVerification: (bool) ($data['require_email_verification'] ?? false),
            auditSensitiveActions: (bool) ($data['audit_sensitive_actions'] ?? true),
            singleSessionPerUser: (bool) ($data['single_session_per_user'] ?? false),
            allowAccountDeletion: (bool) ($data['allow_account_deletion'] ?? true),
            sessionLifetimeMinutes: (int) $data['session_lifetime_minutes'],
            loginMaxAttempts: (int) $data['login_max_attempts'],
            loginDecayMinutes: (int) $data['login_decay_minutes'],
            passwordConfirmationTimeoutSeconds: (int) $data['password_confirmation_timeout_seconds'],
        );
    }
}
