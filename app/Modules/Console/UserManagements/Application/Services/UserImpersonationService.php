<?php

namespace App\Modules\Console\UserManagements\Application\Services;

use App\Models\User;
use App\Modules\Console\AuditLogs\Application\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserImpersonationService
{
    public const SESSION_IMPERSONATOR_ID = 'impersonator_id';

    public const SESSION_IMPERSONATOR_NAME = 'impersonator_name';

    public const SESSION_IMPERSONATOR_EMAIL = 'impersonator_email';

    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function canStart(Request $request, User $target): ?string
    {
        $impersonator = $request->user();

        if (! $impersonator instanceof User) {
            return 'Kamu harus login untuk memakai fitur impersonate.';
        }

        if ($impersonator->is($target)) {
            return 'Tidak bisa impersonate akun sendiri.';
        }

        if ($target->isSuperAdmin()) {
            return 'Tidak bisa impersonate akun super-system.';
        }

        if ($request->session()->has(self::SESSION_IMPERSONATOR_ID)) {
            return 'Selesaikan impersonate aktif terlebih dahulu sebelum berpindah ke user lain.';
        }

        if (! $impersonator->can('users.impersonate')) {
            return 'Akun kamu belum memiliki permission users.impersonate.';
        }

        return null;
    }

    public function start(Request $request, User $target): void
    {
        /** @var User $impersonator */
        $impersonator = $request->user();

        $request->session()->put([
            self::SESSION_IMPERSONATOR_ID => $impersonator->id,
            self::SESSION_IMPERSONATOR_NAME => $impersonator->name,
            self::SESSION_IMPERSONATOR_EMAIL => $impersonator->email,
        ]);

        Auth::login($target);
        $request->session()->regenerate();

        $this->audit->record(
            module: 'user-management',
            event: 'user.impersonation_started',
            auditable: $target,
            description: "Started impersonating {$target->email}",
            newValues: [
                'impersonator_id' => $impersonator->id,
                'impersonator_email' => $impersonator->email,
                'target_id' => $target->id,
                'target_email' => $target->email,
            ],
            actor: $impersonator,
        );
    }

    public function stop(Request $request): ?User
    {
        $impersonatorId = $request->session()->get(self::SESSION_IMPERSONATOR_ID);
        $impersonated = $request->user();

        if (! $impersonatorId) {
            return null;
        }

        $impersonator = User::query()->find($impersonatorId);

        if (! $impersonator) {
            $this->clear($request);

            return null;
        }

        Auth::login($impersonator);
        $this->clear($request);
        $request->session()->regenerate();

        $this->audit->record(
            module: 'user-management',
            event: 'user.impersonation_stopped',
            auditable: $impersonated,
            description: "Stopped impersonating {$impersonated?->email}",
            newValues: [
                'impersonator_id' => $impersonator->id,
                'impersonator_email' => $impersonator->email,
                'target_id' => $impersonated?->id,
                'target_email' => $impersonated?->email,
            ],
            actor: $impersonator,
        );

        return $impersonator;
    }

    public function clear(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_IMPERSONATOR_ID,
            self::SESSION_IMPERSONATOR_NAME,
            self::SESSION_IMPERSONATOR_EMAIL,
        ]);
    }
}
