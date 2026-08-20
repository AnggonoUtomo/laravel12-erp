<?php

namespace App\Modules\Console\SystemSettings\Infrastructure\Jobs;

use App\Models\User;
use App\Modules\Console\SystemSettings\Infrastructure\Notifications\UserActivationLinkNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Password;

class SendUserActivationLinkJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $userId,
    ) {
        $this->onQueue('mail');
    }

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if (! $user) {
            return;
        }

        $token = Password::broker()->createToken($user);

        $user->notify(new UserActivationLinkNotification($token));
    }
}
