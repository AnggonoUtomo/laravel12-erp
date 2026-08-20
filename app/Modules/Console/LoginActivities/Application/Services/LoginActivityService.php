<?php

namespace App\Modules\Console\LoginActivities\Application\Services;

use App\Models\User;
use App\Modules\Console\LoginActivities\Infrastructure\Models\LoginActivity;
use Illuminate\Http\Request;
use Throwable;

class LoginActivityService
{
    public function recordSuccess(Request $request, User $user): void
    {
        $this->record($request, 'login', true, $user, $user->email, 'Login berhasil.');
    }

    public function recordFailure(Request $request, ?User $user = null, ?string $message = null): void
    {
        $this->record($request, 'login_failed', false, $user, $request->string('email')->toString(), $message ?: 'Login gagal.');
    }

    public function recordLogout(Request $request, User $user): void
    {
        $this->record($request, 'logout', true, $user, $user->email, 'Logout berhasil.');
    }

    private function record(Request $request, string $event, bool $successful, ?User $user, ?string $email, ?string $message): void
    {
        try {
            $agent = (string) $request->userAgent();

            LoginActivity::query()->create([
                'user_id' => $user?->id,
                'email' => $email,
                'event' => $event,
                'successful' => $successful,
                'ip_address' => $request->ip(),
                'user_agent' => $agent,
                'device' => $this->device($agent),
                'browser' => $this->browser($agent),
                'platform' => $this->platform($agent),
                'message' => $message,
                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function device(string $agent): string
    {
        if (preg_match('/Mobile|Android|iPhone|iPad/i', $agent)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    private function browser(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Unknown',
        };
    }

    private function platform(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            default => 'Unknown',
        };
    }
}
