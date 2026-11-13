<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

        return $setting;
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
}
