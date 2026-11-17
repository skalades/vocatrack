<?php

namespace App\Channels;

use App\Services\WhatsAppGatewayService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

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
            $name = $notifiable->name ?? 'User #' . ($notifiable->id ?? 'unknown');
            $role = $notifiable->role ?? 'unknown';
            Log::warning("[WhatsAppChannel] Dilewati: {$name} (Role: {$role}) belum memiliki nomor WhatsApp/telepon valid.");
            return;
        }

        // Cek preferensi notifikasi bila notifiable memilikinya
        if (method_exists($notifiable, 'wantsWhatsAppNotification')) {
            $type = method_exists($notification, 'getNotificationType')
                ? $notification->getNotificationType()
                : 'general';

            if (!$notifiable->wantsWhatsAppNotification($type)) {
                Log::info("[WhatsAppChannel] Notifikasi ke {$waNumber} diabaikan sesuai preferensi pengguna tipe: {$type}.");
                return;
            }
        }

        $message = $notification->toWhatsApp($notifiable);
        if (blank($message)) {
            Log::warning("[WhatsAppChannel] Konten teks pesan WhatsApp kosong untuk user #{$notifiable->id}.");
            return;
        }

        $sent = $this->gateway->sendMessage($waNumber, $message);
        if (!$sent) {
            Log::warning("[WhatsAppChannel] Gagal mengirim pesan notifikasi ke nomor {$waNumber} (User: " . ($notifiable->name ?? $notifiable->id) . ").");
        }
    }
}
