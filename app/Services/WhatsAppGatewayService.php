<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    /**
     * In-memory message records for testing / simulation.
     */
    protected static array $sentMessages = [];
    protected static bool $fake = false;

    public static function fake(): void
    {
        static::$fake = true;
        static::$sentMessages = [];
    }

    public static function getSentMessages(): array
    {
        return static::$sentMessages;
    }

    public static function resetFake(): void
    {
        static::$fake = false;
        static::$sentMessages = [];
    }

    /**
     * Normalisasi nomor telepon ke format internasional Indonesia (62xxxxxxxxxx).
     */
    public static function normalizePhoneNumber(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        // Hapus karakter non-digit
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        if (empty($cleaned)) {
            return null;
        }

        // 08xx -> 628xx
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Validasi nomor telepon format Indonesia.
     */
    public static function isValidIndonesianNumber(?string $phone): bool
    {
        $normalized = self::normalizePhoneNumber($phone);
        if (!$normalized) {
            return false;
        }

        // Panjang nomor valid Indonesia: 62 + 9 s.d 13 digit (mis. 628123456789)
        return (bool) preg_match('/^62[0-9]{9,13}$/', $normalized);
    }

    /**
     * Kirim pesan WhatsApp melalui gateway yang terkonfigurasi.
     */
    public function sendMessage(string $targetPhone, string $message): bool
    {
        $phone = self::normalizePhoneNumber($targetPhone);

        if (!$phone || !self::isValidIndonesianNumber($phone)) {
            Log::warning("[WhatsAppGateway] Nomor telepon tidak valid: {$targetPhone}");
            return false;
        }

        if (static::$fake) {
            static::$sentMessages[] = [
                'target' => $phone,
                'message' => $message,
                'sent_at' => now()->toIso8601String(),
            ];
            Log::info("[WhatsAppGateway Fake] Mengirim ke {$phone}: {$message}");
            return true;
        }

        $provider = $this->getSetting('whatsapp_provider', config('services.whatsapp.provider', 'log'));
        $token = $this->getSetting('whatsapp_token', config('services.whatsapp.token', ''));
        $url = $this->getSetting('whatsapp_url', config('services.whatsapp.url', ''));

        // Fallback ke log jika tidak ada kredensial atau mode 'log'
        if ($provider === 'log' || (empty($token) && empty($url))) {
            Log::info("[WhatsAppGateway LOG] Ke: {$phone} | Isi: {$message}");
            static::$sentMessages[] = [
                'target' => $phone,
                'message' => $message,
                'sent_at' => now()->toIso8601String(),
            ];
            return true;
        }

        try {
            if ($provider === 'fonnte') {
                $apiUrl = $url ?: 'https://api.fonnte.com/send';
                $response = Http::timeout(10)
                    ->withHeaders(['Authorization' => $token])
                    ->post($apiUrl, [
                        'target' => $phone,
                        'message' => $message,
                        'countryCode' => '62',
                    ]);

                if ($response->successful()) {
                    Log::info("[WhatsAppGateway Fonnte] Sukses terkirim ke {$phone}");
                    return true;
                }

                Log::error("[WhatsAppGateway Fonnte] Gagal: HTTP {$response->status()} - {$response->body()}");
                return false;
            }

            if ($provider === 'wablas') {
                $apiUrl = $url ?: 'https://bdg.wablas.com/api/send-message';
                $response = Http::timeout(10)
                    ->withHeaders(['Authorization' => $token])
                    ->post($apiUrl, [
                        'phone' => $phone,
                        'message' => $message,
                    ]);

                if ($response->successful()) {
                    Log::info("[WhatsAppGateway Wablas] Sukses terkirim ke {$phone}");
                    return true;
                }

                Log::error("[WhatsAppGateway Wablas] Gagal: HTTP {$response->status()} - {$response->body()}");
                return false;
            }

            // Provider custom / webhook generic (Baileys self-hosted)
            $apiUrl = $url ?: '';
            if ($apiUrl) {
                $response = Http::timeout(10)
                    ->withHeaders(['Authorization' => "Bearer {$token}"])
                    ->post($apiUrl, [
                        'phone' => $phone,
                        'message' => $message,
                    ]);

                if ($response->successful()) {
                    Log::info("[WhatsAppGateway Custom] Sukses terkirim ke {$phone}");
                    return true;
                }

                Log::error("[WhatsAppGateway Custom] Gagal kirim ke {$phone}: HTTP {$response->status()} - {$response->body()}");
                return false;
            }

            Log::warning("[WhatsAppGateway] Provider '{$provider}' tidak dikenal.");
            return false;
        } catch (\Throwable $e) {
            Log::error("[WhatsAppGateway] Exception saat mengirim pesan ke {$phone}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil pengaturan dari PlatformSetting jika ada, atau fallback ke default.
     */
    protected function getSetting(string $key, $default = null)
    {
        try {
            if (class_exists(PlatformSetting::class)) {
                $setting = PlatformSetting::where('key', $key)->first();
                if ($setting && filled($setting->value)) {
                    return $setting->value;
                }
            }
        } catch (\Throwable) {
            // DB table might not exist in certain testing environments
        }

        return $default;
    }
}
