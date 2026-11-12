<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Tampilkan formulir masuk ke sistem VokaTrack.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Verifikasi integritas status aktif akun sebelum redirect
            if (!$user->is_active || (!$user->hasRole('super-admin') && (!$user->school || !$user->school->is_active))) {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();

                return view('auth.login')->withErrors([
                    'email' => 'Sesi Anda telah dihentikan karena akun atau sekolah Anda sedang nonaktif.',
                ]);
            }

            return $this->redirectBasedOnRole($user);
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi masuk dengan proteksi brute-force per-akun dan normalisasi input.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $rawIdentifier = trim($request->input('email'));
        $email = Str::lower($rawIdentifier);

        // Dukungan login cerdas bagi Siswa menggunakan NISN
        if (!filter_var($rawIdentifier, FILTER_VALIDATE_EMAIL)) {
            $student = Student::where('nisn', $rawIdentifier)->with('user')->first();
            if ($student && $student->user) {
                $email = Str::lower($student->user->email);
            }
        }

        $throttleKey = $this->throttleKey($email, $request->ip());

        // 1. Periksa batas percobaan gagal (Rate Limiting)
        $this->ensureIsNotRateLimited($throttleKey);

        $credentials = [
            'email' => $email,
            'password' => (string) $request->input('password'),
        ];

        // 2. Coba autentikasi kredensial
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Validasi akun aktif
            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun Anda telah dinonaktifkan oleh administrator.',
                ])->onlyInput('email');
            }

            // Validasi sekolah aktif (khusus non super-admin)
            if (!$user->hasRole('super-admin')) {
                if (!$user->school || !$user->school->is_active) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors([
                        'email' => 'Sekolah Anda saat ini nonaktif. Hubungi Admin.',
                    ])->onlyInput('email');
                }
            }

            // Berhasil login: reset hit rate limiting dan regenerasi session ID
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return $this->redirectBasedOnRole($user, true);
        }

        // 3. Autentikasi gagal: tambah hit pada rate limiter (maksimal 5x gagal)
        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'Kombinasi email dan kata sandi tidak cocok dengan data kami.',
        ])->onlyInput('email');
    }

    /**
     * Proses keluar dari sistem (Mendukung POST & GET yang aman dan tangguh).
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }

    /**
     * Arahkan pengguna ke dasbor sesuai peran, dengan perlindungan anti-loop dan intended URL.
     */
    protected function redirectBasedOnRole(User $user, bool $checkIntended = false)
    {
        $roleRoutes = [
            'super-admin' => 'superadmin.dashboard',
            'admin-sekolah' => 'adminsekolah.dashboard',
            'kepala-sekolah' => 'kepsek.dashboard',
            'guru-pembimbing' => 'guru.dashboard',
            'pembimbing-dudi' => 'dudi.dashboard',
            'siswa' => 'student.home',
        ];

        $targetRoute = $roleRoutes[$user->role] ?? null;

        // Cegah redirect loop jika peran tidak dikenali
        if (!$targetRoute) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Peran akun pengguna Anda tidak terdaftar dalam konfigurasi sistem. Hubungi administrator.',
            ]);
        }

        $defaultRedirect = redirect()->route($targetRoute);

        // Jika terdapat intended URL yang sesuai dengan hak akses role pengguna, arahkan kembali ke sana
        if ($checkIntended && session()->has('url.intended')) {
            $intendedUrl = (string) session()->get('url.intended');

            $allowedPrefixes = [
                'super-admin' => ['/super-admin', '/notifications'],
                'admin-sekolah' => ['/admin-sekolah', '/notifications'],
                'kepala-sekolah' => ['/kepala-sekolah', '/notifications'],
                'guru-pembimbing' => ['/guru', '/notifications'],
                'pembimbing-dudi' => ['/dudi', '/notifications'],
                'siswa' => ['/siswa', '/notifications'],
            ];

            $prefixes = $allowedPrefixes[$user->role] ?? [];
            $isValidIntended = false;
            foreach ($prefixes as $prefix) {
                if (str_contains($intendedUrl, $prefix)) {
                    $isValidIntended = true;
                    break;
                }
            }

            if ($isValidIntended) {
                return redirect()->intended(route($targetRoute));
            }
        }

        return $defaultRedirect;
    }

    /**
     * Dapatkan throttle key berbasis kombinasi identifier pengguna dan IP klien.
     */
    protected function throttleKey(string $identifier, string $ip): string
    {
        return Str::transliterate(Str::lower($identifier) . '|' . $ip);
    }

    /**
     * Pastikan request login belum melebihi batas percobaan gagal rate limiting.
     */
    protected function ensureIsNotRateLimited(string $throttleKey): void
    {
        $maxAttempts = 5;
        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan masuk yang gagal. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }
    }
}

