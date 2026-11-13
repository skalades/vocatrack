<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->hasRole('super-admin')) {
                return $next($request);
            }

            if (!$user->school_id || !$user->school) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Akun Anda tidak terikat dengan data sekolah yang valid.',
                ]);
            }

            if (!$user->school->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Sekolah Anda saat ini sedang dinonaktifkan oleh administrator.',
                ]);
            }

            $subscription = $user->school->activeSubscription();
            if ($subscription && $subscription->isExpired() && !$subscription->isGracePeriod()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Masa sewa/lisensi platform sekolah Anda telah berakhir (' . $subscription->end_date->format('d M Y') . '). Silakan hubungi Super Administrator untuk perpanjangan.',
                ]);
            }

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Akun pengguna Anda telah dinonaktifkan.',
                ]);
            }

            // Share school context globally to views
            view()->share('currentSchool', $user->school);
        }

        return $next($request);
    }
}
