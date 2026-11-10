<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Akun Anda telah dinonaktifkan.']);
            }

            if (!$user->hasRole('super-admin')) {
                if (!$user->school || !$user->school->is_active) {
                    Auth::logout();
                    return back()->withErrors(['email' => 'Sekolah Anda saat ini nonaktif. Hubungi Admin.']);
                }
            }

            $request->session()->regenerate();

            return $this->redirectBasedOnRole($user);
        }

        return back()->withErrors([
            'email' => 'Kombinasi email dan kata sandi tidak cocok dengan data kami.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }

    protected function redirectBasedOnRole($user)
    {
        return match ($user->role) {
            'super-admin' => redirect()->route('superadmin.dashboard'),
            'admin-sekolah' => redirect()->route('adminsekolah.dashboard'),
            'kepala-sekolah' => redirect()->route('kepapsekolah.dashboard'),
            'guru-pembimbing' => redirect()->route('guru.dashboard'),
            'pembimbing-dudi' => redirect()->route('dudi.dashboard'),
            'siswa' => redirect()->route('student.home'),
            default => redirect('/'),
        };
    }
}
