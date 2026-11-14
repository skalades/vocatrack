<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdatePlatformSettingRequest;
use App\Models\PlatformSetting;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function settings()
    {
        $currentLogoUrl = PlatformSetting::getLogoUrl();
        $hasCustomLogo = (bool) PlatformSetting::get('system_logo');
        $platformName = PlatformSetting::get('platform_name', 'VokaTrack');
        $platformTagline = PlatformSetting::get('platform_tagline', 'Enterprise Cloud');
        $appDescription = PlatformSetting::get('app_description', 'Platform PKL SMK Terpadu Berbasis Multi-Tenancy Cloud');
        $maintenanceActive = (bool) PlatformSetting::get('maintenance_active', false);
        $maintenanceNotice = PlatformSetting::get('maintenance_notice', '');

        // SMTP & Integration Settings
        $smtpSettings = [
            'mail_host' => PlatformSetting::get('mail_host', config('mail.mailers.smtp.host')),
            'mail_port' => PlatformSetting::get('mail_port', config('mail.mailers.smtp.port')),
            'mail_username' => PlatformSetting::get('mail_username', config('mail.mailers.smtp.username')),
            'mail_encryption' => PlatformSetting::get('mail_encryption', config('mail.mailers.smtp.encryption', 'tls')),
            'mail_from_address' => PlatformSetting::get('mail_from_address', config('mail.from.address')),
            'mail_from_name' => PlatformSetting::get('mail_from_name', config('mail.from.name', 'VokaTrack Cloud')),
        ];

        // WhatsApp Gateway Settings
        $waSettings = [
            'wa_provider' => PlatformSetting::get('wa_provider', 'fonnte'),
            'wa_api_endpoint' => PlatformSetting::get('wa_api_endpoint', 'https://api.fonnte.com/send'),
            'wa_api_token' => PlatformSetting::get('wa_api_token', ''),
            'wa_sender_phone' => PlatformSetting::get('wa_sender_phone', ''),
        ];

        // Cloud Storage Settings
        $storageSettings = [
            'storage_driver' => PlatformSetting::get('storage_driver', config('filesystems.default', 'public')),
            's3_bucket' => PlatformSetting::get('s3_bucket', ''),
            's3_region' => PlatformSetting::get('s3_region', 'ap-southeast-1'),
        ];

        // System Health & Diagnostics
        $systemHealth = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_driver' => config('database.default'),
            'db_name' => config('database.connections.' . config('database.default') . '.database'),
            'app_env' => app()->environment(),
            'app_debug' => config('app.debug') ? 'Aktif (Warning)' : 'Nonaktif (Production Safe)',
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'storage_writable' => is_writable(storage_path()),
        ];

        return view('superadmin.settings', compact(
            'currentLogoUrl',
            'hasCustomLogo',
            'platformName',
            'platformTagline',
            'appDescription',
            'maintenanceActive',
            'maintenanceNotice',
            'smtpSettings',
            'waSettings',
            'storageSettings',
            'systemHealth'
        ));
    }

    public function updateSettings(UpdatePlatformSettingRequest $request)
    {
        PlatformSetting::set('platform_name', $request->platform_name);
        PlatformSetting::set('platform_tagline', $request->platform_tagline ?: 'Enterprise Cloud');
        PlatformSetting::set('app_description', $request->app_description ?: '');
        PlatformSetting::set('maintenance_active', $request->boolean('maintenance_active') ? '1' : '0');
        PlatformSetting::set('maintenance_notice', $request->maintenance_notice ?: '');

        // Handle SMTP settings
        if ($request->filled('mail_host')) {
            PlatformSetting::set('mail_host', $request->mail_host);
            PlatformSetting::set('mail_port', (string) $request->mail_port);
            PlatformSetting::set('mail_username', $request->mail_username ?: '');
            if ($request->filled('mail_password')) {
                PlatformSetting::set('mail_password', $request->mail_password);
            }
            PlatformSetting::set('mail_encryption', $request->mail_encryption ?: 'tls');
            PlatformSetting::set('mail_from_address', $request->mail_from_address ?: '');
            PlatformSetting::set('mail_from_name', $request->mail_from_name ?: '');
        }

        // Handle WhatsApp settings
        if ($request->filled('wa_provider')) {
            PlatformSetting::set('wa_provider', $request->wa_provider);
            PlatformSetting::set('wa_api_endpoint', $request->wa_api_endpoint ?: '');
            if ($request->filled('wa_api_token')) {
                PlatformSetting::set('wa_api_token', $request->wa_api_token);
            }
            PlatformSetting::set('wa_sender_phone', $request->wa_sender_phone ?: '');
        }

        // Handle Storage settings
        if ($request->filled('storage_driver')) {
            PlatformSetting::set('storage_driver', $request->storage_driver);
            PlatformSetting::set('s3_bucket', $request->s3_bucket ?: '');
            PlatformSetting::set('s3_region', $request->s3_region ?: '');
        }

        // Handle Logo Reset
        if ($request->boolean('reset_logo')) {
            $oldLogo = PlatformSetting::get('system_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            PlatformSetting::forget('system_logo');
        } elseif ($request->hasFile('system_logo')) {
            $oldLogo = PlatformSetting::get('system_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $file = $request->file('system_logo');
            $filename = 'system_logo_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('branding', $filename, 'public');

            PlatformSetting::set('system_logo', $path);
        }

        AuditLogService::log(
            'settings_update',
            'settings',
            "Pengaturan branding, gateway email & integrasi platform diperbarui oleh Super Admin."
        );

        return back()->with('success', 'Pengaturan branding dan konfigurasi sistem berhasil diperbarui.');
    }
}
