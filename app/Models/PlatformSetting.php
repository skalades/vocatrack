<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PlatformSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * Retrieve a setting by its key with cache fallback.
     */
    public static function get(string $key, $default = null)
    {
        try {
            return Cache::rememberForever("platform_setting_{$key}", function () use ($key, $default) {
                if (!Schema::hasTable('platform_settings')) {
                    return $default;
                }
                $setting = static::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            });
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Store or update a setting and bust the cache.
     */
    public static function set(string $key, $value, string $group = 'general'): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("platform_setting_{$key}");
        Cache::forget("platform_system_logo_url");

        if (in_array($key, ['system_logo', 'platform_name', 'platform_tagline'])) {
            static::syncPwaManifest();
        }

        return $setting;
    }

    /**
     * Simpan nilai rahasia (password SMTP, token API) dalam bentuk terenkripsi APP_KEY.
     */
    public static function setSecret(string $key, string $value, string $group = 'secret'): static
    {
        return static::set($key, Crypt::encryptString($value), $group);
    }

    /**
     * Baca nilai rahasia. Nilai lama yang masih polos (sebelum enkripsi) tetap dikembalikan
     * apa adanya agar tidak memutus konfigurasi yang sudah ada.
     */
    public static function getSecret(string $key, ?string $default = null): ?string
    {
        $raw = static::get($key);
        if ($raw === null || $raw === '') {
            return $default;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (DecryptException) {
            return $raw;
        }
    }

    /**
     * Apakah sebuah rahasia sudah tersimpan (tanpa membuka nilainya).
     */
    public static function hasSecret(string $key): bool
    {
        $raw = static::get($key);

        return $raw !== null && $raw !== '';
    }

    /**
     * Delete a setting and clear its cache.
     */
    public static function forget(string $key): void
    {
        try {
            static::where('key', $key)->delete();
        } catch (\Throwable) {}

        Cache::forget("platform_setting_{$key}");
        Cache::forget("platform_system_logo_url");

        if (in_array($key, ['system_logo', 'platform_name', 'platform_tagline'])) {
            static::syncPwaManifest();
        }
    }

    /**
     * Get the active system logo URL (custom or fallback to default asset).
     */
    public static function getLogoUrl(): string
    {
        try {
            return Cache::rememberForever("platform_system_logo_url", function () {
                if (!Schema::hasTable('platform_settings')) {
                    return asset('images/logo.png');
                }
                $logoPath = static::where('key', 'system_logo')->value('value');
                if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                    return asset('storage/' . $logoPath);
                }
                return asset('images/logo.png');
            });
        } catch (\Throwable) {
            return asset('images/logo.png');
        }
    }

    /**
     * Sinkronkan manifest PWA (manifest.webmanifest) dengan logo dan nama platform sistem.
     * Bila superadmin telah mengunggah logo kustom, gunakan logo tersebut.
     */
    public static function syncPwaManifest(): void
    {
        try {
            $logoUrl = static::getLogoUrl();
            $manifestLogo = parse_url($logoUrl, PHP_URL_PATH) ?: '/images/logo.png';
            if (!str_starts_with($manifestLogo, '/')) {
                $manifestLogo = '/' . $manifestLogo;
            }
            $appName = static::get('platform_name', 'VokaTrack');
            $description = static::get('platform_tagline', 'Presensi selfie, jurnal harian, dan laporan PKL untuk siswa SMK.');

            $manifest = [
                'id' => '/siswa/beranda',
                'name' => "{$appName} Siswa — Presensi & Jurnal PKL",
                'short_name' => $appName,
                'description' => $description,
                'lang' => 'id',
                'start_url' => '/siswa/beranda?source=pwa',
                'scope' => '/',
                'display' => 'standalone',
                'orientation' => 'portrait',
                'background_color' => '#F1F4EC',
                'theme_color' => '#0B2B25',
                'categories' => ['education', 'productivity'],
                'icons' => [
                    ['src' => $manifestLogo, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                    ['src' => $manifestLogo, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                    ['src' => $manifestLogo, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
                    ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                    ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ],
                'shortcuts' => [
                    ['name' => 'Presensi', 'url' => '/siswa/absensi', 'icons' => [['src' => $manifestLogo, 'sizes' => '192x192']]],
                    ['name' => 'Jurnal Harian', 'url' => '/siswa/jurnal', 'icons' => [['src' => $manifestLogo, 'sizes' => '192x192']]],
                ],
            ];

            $manifestPath = public_path('manifest.webmanifest');
            @file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PlatformSetting: Gagal sinkronisasi PWA manifest: ' . $e->getMessage());
        }
    }
}
