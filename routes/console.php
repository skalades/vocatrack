<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Ping URL heartbeat saat sukses ("/fail" saat gagal, konvensi Healthchecks.io &
 * BetterStack) agar cron yang mati atau gagal langsung memicu peringatan.
 */
$heartbeat = function (Event $event, ?string $url): Event {
    return $event
        ->pingOnSuccessIf(filled($url), (string) $url)
        ->pingOnFailureIf(filled($url), rtrim((string) $url, '/') . '/fail');
};

$heartbeat(
    Schedule::command('vokatrack:evaluate-absence')->dailyAt('23:55')->withoutOverlapping()->onOneServer(),
    config('services.heartbeat.evaluate_absence')
);

$heartbeat(
    Schedule::command('saas:evaluate-subscriptions')->dailyAt('00:05')->withoutOverlapping()->onOneServer(),
    config('services.heartbeat.evaluate_subscriptions')
);

$heartbeat(
    Schedule::command('vokatrack:backup')->dailyAt(config('backup.schedule_time', '02:00'))->withoutOverlapping(120)->onOneServer(),
    config('services.heartbeat.backup')
);

Schedule::command('vokatrack:check-failed-jobs --minutes=60')->hourly()->withoutOverlapping()->onOneServer();

Artisan::command('vokatrack:test-whatsapp {phone : Nomor WhatsApp tujuan contoh: 08123456789}', function (string $phone, \App\Services\WhatsAppGatewayService $gateway) {
    $this->info("Mengirim pesan uji coba ke {$phone}...");
    $success = $gateway->sendMessage($phone, "✅ *[VokaTrack]* Gateway WhatsApp Fonnte Anda berhasil terhubung dengan sistem VokaTrack!");
    if ($success) {
        $this->info("Pesan berhasil terkirim via Fonnte!");
    } else {
        $this->error("Gagal mengirim pesan. Silakan periksa token Fonnte dan log sistem di storage/logs/laravel.log.");
    }
})->purpose('Menguji koneksi gateway WhatsApp Fonnte');
