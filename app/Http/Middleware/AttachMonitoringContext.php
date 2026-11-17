<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tambahkan konteks tenant ke laporan error Sentry: school_id, role, dan ID pengguna.
 * Sengaja TIDAK mengirim nama, email, NIS, atau data pribadi lain.
 */
class AttachMonitoringContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('sentry') && filled(config('sentry.dsn')) && ($user = $request->user())) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($user): void {
                $scope->setUser(['id' => (string) $user->id]);
                $scope->setTag('role', (string) $user->role);
                $scope->setTag('school_id', (string) ($user->school_id ?? 'platform'));
                $scope->setTag('impersonating', session()->has('impersonator_id') ? 'yes' : 'no');
            });
        }

        return $next($request);
    }
}
