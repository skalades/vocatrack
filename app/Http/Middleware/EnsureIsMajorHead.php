<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsMajorHead
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user->isKajur()) {
            abort(403, 'Akses ditolak: Halaman ini khusus diperuntukkan bagi Kepala Program Keahlian / Kepala Jurusan.');
        }

        return $next($request);
    }
}
