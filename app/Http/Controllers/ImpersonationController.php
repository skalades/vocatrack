<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(User $user, ImpersonationService $service)
    {
        $service->startImpersonation(Auth::user(), $user);

        // Redirect to target user's default dashboard based on their role
        $roleRoutes = [
            'super-admin' => 'superadmin.dashboard',
            'admin-sekolah' => 'adminsekolah.dashboard',
            'guru-pembimbing' => 'guru.dashboard',
            'pembimbing-dudi' => 'dudi.dashboard',
            'kepala-sekolah' => 'kepsek.dashboard',
            'siswa' => 'student.home',
        ];

        $targetRoute = $roleRoutes[$user->role] ?? 'login';

        return redirect()->route($targetRoute)->with('success', "Mode Impersonasi Aktif: Anda sedang masuk sebagai {$user->name} ({$user->role}).");
    }

    public function stop(ImpersonationService $service)
    {
        $impersonator = $service->stopImpersonation();

        $roleRoutes = [
            'super-admin' => 'superadmin.dashboard',
            'admin-sekolah' => 'adminsekolah.dashboard',
        ];

        $targetRoute = $roleRoutes[$impersonator->role] ?? 'login';

        return redirect()->route($targetRoute)->with('success', "Kembali ke akun administrator Anda ({$impersonator->name}).");
    }
}
