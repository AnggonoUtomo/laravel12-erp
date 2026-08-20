<?php

namespace App\Modules\Console\SystemSettings\Infrastructure\Jobs;

use App\Models\User;
use App\Modules\Console\SystemSettings\Infrastructure\Notifications\UserCredentialNotification;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendUserCredentialNotificationJob implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $userId,
        private readonly string $plainPassword,
    ) {
        $this->onQueue('mail');
    }

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if (! $user) {
            return;
        }

        $user->notify(new UserCredentialNotification($this->plainPassword));
    }
}
