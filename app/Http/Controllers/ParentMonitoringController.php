<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\ParentMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentMonitoringController extends Controller
{
    /**
     * Tampilkan Portal Publik Monitoring Siswa Khusus Orang Tua / Wali (Tanpa Login).
     */
    public function show(string $token, ParentMonitoringService $service)
    {
        $student = $service->findStudentByToken($token);

        if (!$student) {
            return response()->view('public.parent-monitor', [
                'status' => 'not_found',
                'message' => 'Tautan monitoring tidak valid atau telah diperbarui. Silakan hubungi putra/putri Anda untuk mendapatkan tautan terbaru.',
            ], 404);
        }

        $blockReason = $service->portalBlockReason($student);
        if ($blockReason !== null) {
            return response()->view('public.parent-monitor', [
                'status' => 'disabled',
                'student' => $student,
                'message' => $blockReason,
            ], 403);
        }

        $data = $service->getMonitoringData($student);

        return view('public.parent-monitor', array_merge([
            'status' => 'active',
        ], $data));
    }

    /**
     * Buat ulang (regenerate) link monitoring orang tua untuk keamanan.
     * Hanya dapat diakses oleh siswa pemilik akun atau admin sekolah.
     */
    public function regenerate(Request $request, ParentMonitoringService $service)
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            abort(403, 'Anda tidak memiliki hak akses untuk memperbarui tautan ini.');
        }

        $service->regenerateToken($student);

        // Siswa dapat memutus akses orang tua dengan membuat ulang tautan; catat agar
        // sekolah dapat menelusuri dan menerbitkan ulang tautan bila disalahgunakan.
        AuditLogService::log(
            action: 'parent_token_regenerated_by_student',
            module: 'parent_portal',
            description: "Siswa {$user->name} membuat ulang tautan portal monitoring orang tua (tautan lama dinonaktifkan).",
            after: ['student_id' => $student->id],
        );

        return redirect()->route('student.profile')->with('success', 'Tautan monitoring orang tua berhasil diperbarui! Tautan lama kini sudah tidak dapat diakses.');
    }
}
