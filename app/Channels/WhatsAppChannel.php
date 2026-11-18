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

        // Periksa apakah tenant sekolah mengaktifkan fitur WhatsApp Gateway
        $schoolId = $notifiable->school_id
            ?? $notifiable->student?->school_id
            ?? $notifiable->teacher?->school_id
            ?? $notifiable->school?->id
            ?? null;

        if ($schoolId) {
            $settings = \App\Models\SchoolSetting::allForSchool((int) $schoolId);
            $isWaEnabled = isset($settings['feature_whatsapp'])
                ? filter_var($settings['feature_whatsapp'], FILTER_VALIDATE_BOOLEAN)
                : true;

            if (!$isWaEnabled) {
                Log::info("[WhatsAppChannel] Notifikasi ke {$waNumber} dilewati karena fitur WhatsApp Gateway dinonaktifkan untuk sekolah #{$schoolId}.");
                return;
            }
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
