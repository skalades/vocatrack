<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Student;
use App\Models\StudentPkl;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ParentMonitoringService
{
    /**
     * Cari siswa berdasarkan parent token dengan validasi status portal aktif.
     */
    public function findStudentByToken(string $token): ?Student
    {
        if (trim($token) === '') {
            return null;
        }

        return Student::where('parent_token', $token)
            ->with([
                'user.school',
                'class.major',
            ])
            ->first();
    }

    /**
     * Dapatkan ringkasan komprehensif aktivitas PKL siswa untuk portal orang tua.
     */
    public function getMonitoringData(Student $student): array
    {
        $today = Carbon::today()->toDateString();

        // Ambil penugasan PKL aktif atau penugasan terakhir
        $spkl = $student->studentPkls()
            ->with([
                'company',
                'pklPeriod',
                'teacher.user',
                'supervisor.user',
            ])
            ->whereIn('status', ['active', 'assigned', 'completed'])
            ->latest()
            ->first();

        if (!$spkl) {
            return [
                'has_pkl' => false,
                'student' => $student,
                'spkl' => null,
                'today_attendance' => null,
                'attendance_stats' => [
                    'hadir' => 0,
                    'terlambat' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'alfa' => 0,
                    'total_recorded' => 0,
                    'rate' => 0,
                ],
                'recent_attendances' => collect(),
                'recent_journals' => collect(),
            ];
        }

        // Kehadiran Hari Ini
        $todayAttendance = Attendance::where('student_pkl_id', $spkl->id)
            ->whereDate('date', $today)
            ->first();

        // Statistik Kehadiran Kumulatif
        $attendances = Attendance::where('student_pkl_id', $spkl->id)->get();
        $stats = [
            'hadir' => $attendances->where('status', 'hadir')->count(),
            'terlambat' => $attendances->where('status', 'terlambat')->count(),
            'izin' => $attendances->where('status', 'izin')->count(),
            'sakit' => $attendances->where('status', 'sakit')->count(),
            'dinas_luar' => $attendances->whereIn('status', ['dinas_luar', 'luar_kantor'])->count(),
            'alfa' => $attendances->where('status', 'alfa')->count(),
            'total_recorded' => $attendances->count(),
        ];

        $effectivePresence = $stats['hadir'] + $stats['terlambat'] + $stats['dinas_luar'];
        $stats['rate'] = $stats['total_recorded'] > 0
            ? round(($effectivePresence / $stats['total_recorded']) * 100)
            : 0;

        // Riwayat 7 Hari Presensi Terakhir
        $recentAttendances = Attendance::where('student_pkl_id', $spkl->id)
            ->latest('date')
            ->take(7)
            ->get();

        // 5 Jurnal Kegiatan Terkini
        $recentJournals = Journal::where('student_pkl_id', $spkl->id)
            ->with('photos')
            ->latest('date')
            ->take(5)
            ->get();

        return [
            'has_pkl' => true,
            'student' => $student,
            'spkl' => $spkl,
            'today_attendance' => $todayAttendance,
            'attendance_stats' => $stats,
            'recent_attendances' => $recentAttendances,
            'recent_journals' => $recentJournals,
        ];
    }

    /**
     * Buat ulang parent token unik untuk keamanan jika link bocor.
     */
    public function regenerateToken(Student $student): string
    {
        return $student->regenerateParentToken();
    }

    /**
     * Aktifkan atau nonaktifkan akses portal orang tua.
     */
    public function togglePortal(Student $student, bool $isActive): void
    {
        $student->update(['is_parent_portal_active' => $isActive]);
    }
}
