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

        // Share system branding globally across all views
        View::composer('*', function ($view) {
            $view->with([
                'systemLogo' => PlatformSetting::getLogoUrl(),
                'systemName' => PlatformSetting::get('platform_name', 'VokaTrack'),
                'systemTagline' => PlatformSetting::get('platform_tagline', 'Enterprise Cloud'),
            ]);
        });
    }
}

