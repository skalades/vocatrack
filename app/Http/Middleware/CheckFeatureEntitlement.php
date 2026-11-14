<?php

namespace App\Http\Middleware;

use App\Services\FeatureGateService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureEntitlement
{
    public function __construct(
        protected FeatureGateService $featureGateService
    ) {}

    /**
     * Handle an incoming request and check if user's school has the required feature.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Super Admin always has full access
            if ($user->hasRole('super-admin')) {
                return $next($request);
            }

            $school = $user->school;
            if ($school && !$this->featureGateService->isFeatureEnabled($school, $feature)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => "Akses ditolak: Fitur '{$feature}' tidak tersedia pada paket langganan sekolah Anda.",
                    ], 403);
                }

                return back()->with('error', "Fitur ini tidak tersedia pada paket langganan sekolah Anda. Silakan hubungi Super Administrator untuk peningkatan paket lisensi.");
            }
        }

        return $next($request);
    }
}
