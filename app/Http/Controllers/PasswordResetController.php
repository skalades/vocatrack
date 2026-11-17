<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Models\StudentPasswordResetRequest;
use App\Models\User;
use App\Notifications\StudentPasswordResetRequestedNotification;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    /**
     * Tampilkan formulir permohonan reset kata sandi untuk siswa.
     */
    public function showForgotForm()
    {
        $systemName = PlatformSetting::get('system_name', 'VokaTrack');
        $systemLogo = PlatformSetting::get('system_logo', null);

        return view('auth.forgot-password', compact('systemName', 'systemLogo'));
    }

    /**
     * Tangani pengajuan permohonan reset kata sandi.
     * Siswa memasukkan email, sistem mencari guru pembimbing dan mengirim notifikasi multi-channel.
     */
    public function requestReset(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:255'],
        ], [
            'email.required' => 'Silakan masukkan alamat email akun siswa Anda.',
        ]);

        $email = trim(strtolower($validated['email']));

        // Cari user berdasarkan email
        $user = User::where('email', $email)->first();

        if ($user && $user->role === 'siswa' && $user->student) {
            $student = $user->student;

            // Cari alokasi PKL guru pembimbing siswa ini
            $assignment = $student->studentPkls()
                ->whereNotNull('teacher_id')
                ->whereIn('status', ['assigned', 'active'])
                ->latest()
                ->first()
                ?? $student->studentPkls()
                    ->whereNotNull('teacher_id')
                    ->latest()
                    ->first();

            // Batasi spam: jika sudah ada permohonan pending dalam 15 menit terakhir, update timestamp-nya
            $recentRequest = StudentPasswordResetRequest::where('student_id', $student->id)
                ->where('status', 'pending')
                ->where('created_at', '>=', now()->subMinutes(15))
                ->latest()
                ->first();

            if (!$recentRequest) {
                $resetRequest = StudentPasswordResetRequest::create([
                    'school_id' => $user->school_id,
                    'student_id' => $student->id,
                    'teacher_id' => $assignment?->teacher_id,
                    'email' => $email,
                    'status' => 'pending',
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 500),
                ]);
            } else {
                $resetRequest = $recentRequest;
                $resetRequest->touch();
            }

            // Kirim notifikasi ke guru pembimbing
            if ($assignment?->teacher?->user) {
                $teacherUser = $assignment->teacher->user;
                $teacherUser->notify(new StudentPasswordResetRequestedNotification($student, $resetRequest));
            } else {
                // Fallback jika belum dialokasikan ke guru: kirim ke admin sekolah
                $admins = User::where('school_id', $user->school_id)
                    ->where('role', 'admin-sekolah')
                    ->where('is_active', true)
                    ->get();

                foreach ($admins as $admin) {
                    $admin->notify(new StudentPasswordResetRequestedNotification($student, $resetRequest));
                }
            }

            // Catat audit log
            AuditLogService::log(
                action: 'password_reset_requested',
                module: 'security',
                description: "Siswa '{$user->name}' (NISN: {$student->nisn}) mengajukan permohonan reset kata sandi ke guru pembimbing.",
                school: $user->school,
                user: $user
            );
        } else {
            // Anti-enumerasi: Tetap berikan log internal tanpa membocorkan ke pengguna luar
            Log::info("[PasswordReset] Permintaan reset untuk email: {$email} (user tidak ditemukan atau bukan siswa).");
        }

        return back()->with('status', 'Permohonan reset kata sandi telah dikirim. Guru pembimbing Anda telah menerima notifikasi untuk mereset kata sandi akun Anda. Silakan hubungi guru pembimbing Anda.');
    }
}
