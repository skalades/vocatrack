<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Peringatan operasional untuk staf platform (super admin): backup gagal,
 * job antrean gagal, dsb. Dikirim in-app dan via email bila SMTP terkonfigurasi.
 */
class SystemAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $event,
        public string $title,
        public string $message,
        public string $url,
        public string $color = 'rose'
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (self::mailIsConfigured() && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public static function mailIsConfigured(): bool
    {
        $mailer = config('mail.default');

        if (in_array($mailer, ['log', 'array', null], true)) {
            return false;
        }

        if ($mailer === 'smtp') {
            return filled(config('mail.mailers.smtp.host')) && filled(config('mail.mailers.smtp.username'));
        }

        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('[VokaTrack] ' . $this->title)
            ->line($this->message)
            ->action('Buka Panel Super Admin', $this->url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'system_' . $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'color' => $this->color,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
