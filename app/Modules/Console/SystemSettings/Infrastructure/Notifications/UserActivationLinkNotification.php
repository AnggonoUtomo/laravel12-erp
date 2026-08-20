<?php

namespace App\Modules\Console\SystemSettings\Infrastructure\Notifications;

use App\Modules\Console\NotificationTemplates\Application\Services\NotificationTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserActivationLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
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
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
        $template = app(NotificationTemplateService::class)->render('user.activation', [
            'name' => $notifiable->name,
            'email' => $notifiable->email,
            'action_url' => $url,
            'app_name' => config('app.name'),
        ]);

        return (new MailMessage)
            ->subject($template['subject'] ?: 'Aktivasi akun dan atur password')
            ->greeting('Halo '.$notifiable->name.',')
            ->line($template['body'] ?: 'Akun kamu sudah dibuat. Gunakan tautan berikut untuk aktivasi dan mengatur password.')
            ->action('Aktivasi dan Atur Password', $url)
            ->line('Tautan ini bersifat rahasia. Abaikan email ini jika kamu tidak merasa perlu mengaktifkan akun.');
    }
}
