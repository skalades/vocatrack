<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ImpersonationService
{
    /**
     * Check if a given impersonator has authority to log in as target user.
     */
    public function canImpersonate(User $impersonator, User $target): bool
    {
        // Cannot impersonate oneself
        if ($impersonator->id === $target->id) {
            return false;
        }

        // Cannot impersonate inactive account
        if (!$target->is_active) {
            return false;
        }

        // 1. Super Admin: can impersonate any non-superadmin in any school
        if ($impersonator->hasRole('super-admin')) {
            return !$target->hasRole('super-admin');
        }

        // 2. Admin Sekolah: can only impersonate Guru, DU/DI, and Siswa in their own school
        if ($impersonator->hasRole('admin-sekolah')) {
            if ($target->school_id !== $impersonator->school_id) {
                return false;
            }

            return in_array($target->role, ['guru-pembimbing', 'pembimbing-dudi', 'siswa', 'kepala-sekolah']);
        }

        return false;
    }

    /**
     * Start impersonating the target user.
     */
    public function startImpersonation(User $impersonator, User $target): void
    {
        if (!$this->canImpersonate($impersonator, $target)) {
            throw ValidationException::withMessages([
                'impersonate' => 'Akses ditolak: Anda tidak memiliki wewenang meng-impersonate pengguna ini.'
            ]);
        }

        session([
            'impersonator_id' => $impersonator->id,
            'impersonator_original_role' => $impersonator->role,
            'impersonator_name' => $impersonator->name,
        ]);

        Log::warning("[SECURITY AUDIT] User ID {$impersonator->id} ({$impersonator->email}, role: {$impersonator->role}) started impersonating User ID {$target->id} ({$target->email}, role: {$target->role}).");

        Auth::login($target);
    }

    /**
     * Stop impersonation and return back to the original administrator session.
     */
    public function stopImpersonation(): User
    {
        if (!$this->isImpersonating()) {
            throw ValidationException::withMessages([
                'impersonate' => 'Sesi impersonasi aktif tidak ditemukan.'
            ]);
        }

        $impersonatorId = session('impersonator_id');
        $impersonator = User::findOrFail($impersonatorId);

        session()->forget(['impersonator_id', 'impersonator_original_role', 'impersonator_name']);

        Log::info("[SECURITY AUDIT] User ID {$impersonator->id} ({$impersonator->email}) stopped impersonation and returned to administrator session.");

        Auth::login($impersonator);

        return $impersonator;
    }

    /**
     * Check if currently in an active impersonation session.
     */
    public function isImpersonating(): bool
    {
        return session()->has('impersonator_id');
    }

    /**
     * Get original administrator user object if impersonating.
     */
    public function getImpersonator(): ?User
    {
        if (!$this->isImpersonating()) {
            return null;
        }

        return User::find(session('impersonator_id'));
    }
}
