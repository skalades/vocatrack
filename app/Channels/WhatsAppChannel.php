<?php

namespace App\Channels;

use App\Services\WhatsAppGatewayService;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    public function __construct(
        protected WhatsAppGatewayService $gateway
    ) {}

    /**
     * Kirim notifikasi melalui WhatsApp.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $waNumber = $notifiable->formatted_wa_number ?? $notifiable->wa_number ?? $notifiable->profile?->phone ?? null;
        if (blank($waNumber)) {
            return;
        }

        // Cek preferensi notifikasi bila notifiable memilikinya
        if (method_exists($notifiable, 'wantsWhatsAppNotification')) {
            $type = method_exists($notification, 'getNotificationType')
                ? $notification->getNotificationType()
                : 'general';

            if (!$notifiable->wantsWhatsAppNotification($type)) {
                return;
            }
        }

        $message = $notification->toWhatsApp($notifiable);
        if (blank($message)) {
            return;
        }

        $this->gateway->sendMessage($waNumber, $message);
    }
}
