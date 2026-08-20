<?php

namespace App\Modules\Console\SystemSettings\Infrastructure\Notifications;

use App\Modules\Console\NotificationTemplates\Application\Services\NotificationTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCredentialNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $plainPassword,
        private readonly ?string $subject = null,
        private readonly ?string $intro = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = app(NotificationTemplateService::class)->render('user.credential', [
            'name' => $notifiable->name,
            'email' => $notifiable->email,
            'password' => $this->plainPassword,
            'login_url' => route('login'),
            'app_name' => config('app.name'),
        ]);

        return (new MailMessage)
            ->subject($this->subject ?: ($template['subject'] ?: 'Informasi akses akun'))
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->intro ?: ($template['body'] ?: 'Berikut informasi akses akun kamu.'))
            ->action('Login', route('login'))
            ->line('Demi keamanan, segera ubah kata sandi setelah berhasil login.');
    }
}
