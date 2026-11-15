<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
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
                // Allow Super Admin during impersonation to still access tenant for auditing/troubleshooting
                if (session('impersonator_original_role') === 'super-admin') {
                    view()->share('tenantExpiredWarning', true);
                } else {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->withErrors([
                        'email' => 'Masa sewa/lisensi platform sekolah Anda telah berakhir (' . $subscription->end_date->format('d M Y') . '). Silakan hubungi Super Administrator untuk perpanjangan.',
                    ]);
                }
            }

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Akun pengguna Anda telah dinonaktifkan.',
                ]);
            }

            // Akun dengan kata sandi sementara (dibuat/direset admin) wajib menggantinya dulu.
            // Halaman tetap bisa dibuka (modal ganti sandi tampil otomatis), tetapi aksi tulis diblokir.
            // Saat diimpersonasi, staf platform tidak dipaksa mengganti sandi milik pengguna.
            if ($user->must_change_password && !session()->has('impersonator_id')) {
                view()->share('forcePasswordChange', true);

                if (!$request->isMethodSafe()) {
                    return back()->with('error', 'Silakan ganti kata sandi sementara Anda terlebih dahulu sebelum melanjutkan.');
                }
            }

            // Kunci pemeliharaan (read-only): hanya staf platform (impersonasi) yang boleh menulis.
            if (PlatformSetting::get('maintenance_lock', false) && !session()->has('impersonator_id')) {
                if (!$request->isMethodSafe()) {
                    return back()->with('error', PlatformSetting::get('maintenance_notice') ?: 'Platform sedang dalam pemeliharaan. Perubahan data dinonaktifkan sementara.');
                }
            }

            // Share school context globally to views
            view()->share('currentSchool', $user->school);
        }

        return $next($request);
    }
}
