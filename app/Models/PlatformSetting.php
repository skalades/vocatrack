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
     * Ambil path relatif logo sistem aktif untuk PWA manifest & Web Push icon.
     */
    public static function getPwaLogoPath(): string
    {
        $path = parse_url(static::getLogoUrl(), PHP_URL_PATH) ?: '/images/logo.png';

        return str_starts_with($path, '/') ? $path : '/' . $path;
    }

    /**
     * Sinkronkan manifest PWA siswa (manifest.webmanifest) dan guru (manifest-guru.webmanifest)
     * dengan logo dan nama platform sistem.
     */
    public static function syncPwaManifest(): void
    {
        app(\App\Services\PwaManifestService::class)->sync();
    }
}
