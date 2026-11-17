<?php

namespace App\Notifications;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi seputar masa sewa sekolah (tagihan terbit, pengingat jatuh tempo,
 * masa tenggang, pembayaran diterima). Dikirim ke admin sekolah dan staf platform via in-app & email.
 */
class SubscriptionLifecycleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public School $school,
        public string $event,
        public string $title,
        public string $message,
        public string $url,
        public string $color = 'amber'
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function getNotificationType(): string
    {
        return 'subscription_' . $this->event;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[VokaTrack Langganan] {$this->title}")
            ->line($this->message)
            ->action('Lihat Detail Langganan', $this->url)
            ->line('Terima kasih telah mempercayakan pengelolaan program PKL kepada VokaTrack.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_' . $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'school_id' => $this->school->id,
            'url' => $this->url,
            'color' => $this->color,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
