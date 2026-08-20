<?php

namespace App\Modules\Console\SystemSettings\Infrastructure\Mail;

use App\Modules\Console\NotificationTemplates\Application\Services\NotificationTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SmtpTestMail extends Mailable
{
    use Queueable, SerializesModels;

    private array $template;

    public function __construct(
        public readonly string $testedAt,
    ) {
        $this->template = app(NotificationTemplateService::class)->render('smtp.test', [
            'tested_at' => $this->testedAt,
            'app_name' => config('app.name'),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template['subject'] ?: 'SMTP Test Email',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.system-settings.smtp-test',
            with: [
                'body' => $this->template['body'],
            ],
        );
    }
}
