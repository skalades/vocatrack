<?php

namespace App\Console\Commands;

use App\Models\PlatformSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncPwaBranding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vokatrack:sync-pwa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronkan manifest PWA dan logo PWA dengan pengaturan branding sistem';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $customLogo = PlatformSetting::get('system_logo');
        $hasCustom = $customLogo && Storage::disk('public')->exists($customLogo);
        $logoUrl = PlatformSetting::getLogoUrl();
        $appName = PlatformSetting::get('platform_name', 'VokaTrack');

        $this->info("Menyinkronkan PWA branding untuk: {$appName}");
        if ($hasCustom) {
            $this->line("  ✓ Logo kustom superadmin terdeteksi: {$customLogo}");
        } else {
            $this->line("  ℹ Menggunakan logo default sistem: images/logo.png");
        }
        $this->line("  ✓ URL Logo aktif: {$logoUrl}");

        PlatformSetting::syncPwaManifest();

        $this->info("✓ Manifest PWA (public/manifest.webmanifest) berhasil disinkronkan.");

        return self::SUCCESS;
    }
}
