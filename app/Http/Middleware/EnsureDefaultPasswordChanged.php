<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDefaultPasswordChanged
{
    /**
     * Alihkan pengguna ke form ubah kata sandi saat login pertama jika masih memakai sandi default sistem.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && !session()->has('impersonator_id')) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $mustChangeDefault = (bool) session('force_default_password_change', false)
                || (Auth::viaRemember() && $user->isUsingDefaultPassword());

            if ($mustChangeDefault) {
                if (!$user->isUsingDefaultPassword()) {
                    $request->session()->forget('force_default_password_change');
                } elseif (!$this->shouldPassThrough($request)) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Anda wajib mengganti kata sandi default sistem terlebih dahulu sebelum melanjutkan.',
                            'redirect' => route('password.first-login'),
                        ], 423);
                    }

                    return redirect()->route('password.first-login');
                }
            }
        }

        return $next($request);
    }

    protected function shouldPassThrough(Request $request): bool
    {
        return $request->routeIs([
            'password.first-login',
            'password.first-login.update',
            'logout',
            'csrf.token',
            'impersonate.leave',
            'pwa.manifest',
            'pwa.manifest.teacher',
            'public.cert.verify',
            'public.cert.download',
            'parent.monitor',
            'parent.monitor.photo',
        ]);
    }
}
