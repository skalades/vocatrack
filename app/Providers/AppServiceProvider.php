<?php

namespace App\Providers;

use App\Models\PlatformSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Carbon\Carbon::setLocale('id');

        // Prevent N+1 queries during development and automated tests
        Model::preventLazyLoading(! $this->app->isProduction());

        $this->applyPlatformMailSettings();

        // Share system branding globally across all views
        View::composer('*', function ($view) {
            $view->with([
                'systemLogo' => PlatformSetting::getLogoUrl(),
                'systemName' => PlatformSetting::get('platform_name', 'VokaTrack'),
                'systemTagline' => PlatformSetting::get('platform_tagline', 'Enterprise Cloud'),
            ]);
        });
    }

    /**
     * Terapkan konfigurasi SMTP yang diatur Super Admin (Pengaturan Platform) di atas nilai .env.
     * Bila host SMTP belum diisi dari panel, konfigurasi .env tetap dipakai apa adanya.
     */
    protected function applyPlatformMailSettings(): void
    {
        if ($this->app->runningUnitTests()) {
            return;
        }

        $host = PlatformSetting::get('mail_host');
        if (!$host) {
            return;
        }

        $encryption = PlatformSetting::get('mail_encryption', 'tls');

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) PlatformSetting::get('mail_port', 587),
            'mail.mailers.smtp.username' => PlatformSetting::get('mail_username') ?: null,
            'mail.mailers.smtp.password' => PlatformSetting::getSecret('mail_password'),
            // Symfony Mailer: 'smtps' = SSL implisit (port 465); 'smtp' = STARTTLS otomatis bila didukung.
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.from.address' => PlatformSetting::get('mail_from_address') ?: config('mail.from.address'),
            'mail.from.name' => PlatformSetting::get('mail_from_name') ?: config('mail.from.name'),
        ]);
    }
}

