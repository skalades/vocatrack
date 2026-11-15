<?php

namespace App\Notifications;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi in-app seputar masa sewa sekolah (tagihan terbit, pengingat jatuh tempo,
 * masa tenggang, pembayaran diterima). Dikirim ke admin sekolah dan staf platform.
 */
class SubscriptionLifecycleNotification extends Notification
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
        return ['database'];
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
