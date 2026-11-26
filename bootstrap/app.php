<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'IdentifyTenant' => \App\Http\Middleware\IdentifyTenant::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'is-kajur' => \App\Http\Middleware\EnsureIsMajorHead::class,
            'is-walikelas' => \App\Http\Middleware\EnsureIsHomeroomTeacher::class,
            'feature' => \App\Http\Middleware\CheckFeatureEntitlement::class,
        ]);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\AttachMonitoringContext::class,
            \App\Http\Middleware\EnsureDefaultPasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Laporkan exception ke Sentry (no-op bila SENTRY_LARAVEL_DSN kosong).
        \Sentry\Laravel\Integration::handles($exceptions);

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->is('logout') || $request->routeIs('logout')) {
                \Illuminate\Support\Facades\Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('success', 'Sesi Anda telah berakhir. Anda telah keluar dari sistem.');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Token keamanan formulir kedaluwarsa. Silakan coba kirim ulang.',
                    'csrf_expired' => true,
                ], 419);
            }

            $request->session()->regenerateToken();

            if ($request->is('login') || $request->routeIs('login.post')) {
                return redirect()->route('login')
                    ->withInput($request->only('email', 'remember'))
                    ->with('error', 'Halaman masuk terbuka terlalu lama. Token keamanan diperbarui, silakan klik Masuk kembali.');
            }

            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->with('error', 'Token keamanan formulir diperbarui karena halaman lama terbuka. Silakan kirim ulang.');
        });
    })->create();
