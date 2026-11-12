<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Bangun query daftar siswa terisolasi per sekolah untuk manajemen link ortu.
     * Mendukung pemfilteran jurusan, rombel, status token, status aktif portal, pencarian nama/NISN/NIS,
     * serta scoping khusus guru pembimbing.
     */
    public function getStudentsQuery(int $schoolId, array $filters = [], ?int $teacherId = null): Builder
    {
        $query = Student::whereHas('user', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->with([
                'user.profile',
                'class.major',
                'activePkl.company',
                'activePkl.teacher.user',
            ]);

        // Scoping jika diakses oleh Guru Pembimbing
        if ($teacherId) {
            $scope = $filters['scope'] ?? 'bimbingan';
            if ($scope === 'bimbingan') {
                $query->whereHas('studentPkls', function ($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                });
            }
        }

        // Filter berdasarkan Jurusan (Major)
        if (!empty($filters['major_id']) && $filters['major_id'] !== 'all') {
            $query->whereHas('class', function ($q) use ($filters) {
                $q->where('major_id', $filters['major_id']);
            });
        }

        // Filter berdasarkan Rombel / Kelas (SchoolClass)
        if (!empty($filters['class_id']) && $filters['class_id'] !== 'all') {
            $query->where('class_id', $filters['class_id']);
        }

        // Filter status keberadaan token
        if (!empty($filters['token_status']) && $filters['token_status'] !== 'all') {
            if ($filters['token_status'] === 'has_token') {
                $query->whereNotNull('parent_token')->where('parent_token', '!=', '');
            } elseif ($filters['token_status'] === 'missing') {
                $query->where(function ($q) {
                    $q->whereNull('parent_token')->orWhere('parent_token', '');
                });
            }
        }

        // Filter status keaktifan portal
        if (!empty($filters['portal_status']) && $filters['portal_status'] !== 'all') {
            if ($filters['portal_status'] === 'active') {
                $query->where('is_parent_portal_active', true);
            } elseif ($filters['portal_status'] === 'inactive') {
                $query->where('is_parent_portal_active', false);
            }
        }

        // Pencarian teks (Nama, Email, NISN, NIS, atau Nama Kelas)
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhereHas('class', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter ID spesifik (misal ekspor baris terpilih)
        if (!empty($filters['student_ids']) && is_array($filters['student_ids'])) {
            $query->whereIn('id', $filters['student_ids']);
        }

        return $query;
    }

    /**
     * Dapatkan opsi Jurusan dan Rombel untuk dropdown filter sekolah.
     */
    public function getFilterOptions(int $schoolId, ?int $teacherId = null): array
    {
        $majors = Major::where('school_id', $schoolId)->orderBy('name')->get();
        $classesQuery = SchoolClass::where('school_id', $schoolId)->with('major')->orderBy('name');

        $classes = $classesQuery->get();

        return [
            'majors' => $majors,
            'classes' => $classes,
        ];
    }

    /**
     * Generate atau perbarui parent token secara batch berdasarkan daftar ID siswa.
     * Menggunakan pengecekan ketat tenant isolasi per sekolah dan pembimbing jika ada.
     */
    public function batchGenerateTokens(array $studentIds, int $schoolId, bool $forceRegenerate = false, ?int $teacherId = null): int
    {
        if (empty($studentIds)) {
            return 0;
        }

        $query = Student::whereIn('id', $studentIds)
            ->whereHas('user', fn($q) => $q->where('school_id', $schoolId));

        if ($teacherId) {
            $scope = request()->input('scope', 'bimbingan');
            if ($scope === 'bimbingan') {
                $query->whereHas('studentPkls', fn($q) => $q->where('teacher_id', $teacherId));
            }
        }

        $students = $query->get();
        $updatedCount = 0;

        foreach ($students as $student) {
            if ($forceRegenerate || empty($student->parent_token)) {
                $student->update([
                    'parent_token' => Str::random(32),
                    'is_parent_portal_active' => true,
                ]);
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    /**
     * Generate atau perbarui parent token secara batch berdasarkan filter rombel dan jurusan aktif.
     */
    public function batchGenerateByFilter(int $schoolId, array $filters = [], bool $forceRegenerate = false, ?int $teacherId = null): int
    {
        $query = $this->getStudentsQuery($schoolId, $filters, $teacherId);

        if (!$forceRegenerate) {
            $query->where(function ($q) {
                $q->whereNull('parent_token')->orWhere('parent_token', '');
            });
        }

        $updatedCount = 0;
        $query->chunk(150, function ($students) use (&$updatedCount) {
            foreach ($students as $student) {
                $student->update([
                    'parent_token' => Str::random(32),
                    'is_parent_portal_active' => true,
                ]);
                $updatedCount++;
            }
        });

        return $updatedCount;
    }

    /**
     * Toggle status aktif portal orang tua secara massal untuk siswa terpilih.
     */
    public function batchTogglePortal(array $studentIds, int $schoolId, bool $isActive, ?int $teacherId = null): int
    {
        if (empty($studentIds)) {
            return 0;
        }

        $query = Student::whereIn('id', $studentIds)
            ->whereHas('user', fn($q) => $q->where('school_id', $schoolId));

        if ($teacherId) {
            $scope = request()->input('scope', 'bimbingan');
            if ($scope === 'bimbingan') {
                $query->whereHas('studentPkls', fn($q) => $q->where('teacher_id', $teacherId));
            }
        }

        return $query->update(['is_parent_portal_active' => $isActive]);
    }

    /**
     * Susun teks format pesan broadcast WhatsApp yang ramah dan siap dibagikan ke orang tua siswa.
     */
    public function buildWhatsAppMessage(Student $student): string
    {
        $studentName = $student->user?->name ?? 'Siswa';
        $className = $student->class?->name ?? '-';
        $majorName = $student->class?->major?->name ?? '-';
        $url = $student->parent_monitoring_url ?? '(Link belum dibuat)';
        $companyName = $student->activePkl?->company?->name ?? null;

        $msg = "Yth. Bapak/Ibu Orang Tua/Wali dari {$studentName} ({$className} - {$majorName}),\n\n";
        $msg .= "Berikut adalah tautan resmi Portal Pemantauan Aktivitas PKL (Praktik Kerja Lapangan) putra/putri Anda:\n";
        $msg .= "👉 {$url}\n\n";
        if ($companyName) {
            $msg .= "🏢 Mitra DU/DI: {$companyName}\n";
        }
        $msg .= "Melalui tautan ini, Bapak/Ibu dapat memantau presensi harian, jurnal kegiatan industri, serta rekap kehadiran secara langsung tanpa perlu login/kata sandi.\n\n";
        $msg .= "Terima kasih atas bimbingan dan kerja samanya.";

        return $msg;
    }

    /**
     * Ekspor daftar link monitoring orang tua ke file CSV dengan encoding UTF-8 BOM,
     * dikelompokkan dan difilter berdasarkan Jurusan dan Rombel (atau siswa terpilih).
     */
    public function exportStudentsCsvStream(int $schoolId, array $filters = [], ?int $teacherId = null): StreamedResponse
    {
        $query = $this->getStudentsQuery($schoolId, $filters, $teacherId)
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->orderBy('classes.name', 'asc')
            ->orderBy('users.name', 'asc')
            ->select('students.*');

        // Penamaan file dinamis berdasarkan filter jurusan dan rombel
        $classPart = 'Semua_Rombel';
        if (!empty($filters['class_id']) && $filters['class_id'] !== 'all') {
            $cls = SchoolClass::where('school_id', $schoolId)->find($filters['class_id']);
            if ($cls) {
                $classPart = Str::slug($cls->name, '_');
            }
        }

        $majorPart = 'Semua_Jurusan';
        if (!empty($filters['major_id']) && $filters['major_id'] !== 'all') {
            $mj = Major::where('school_id', $schoolId)->find($filters['major_id']);
            if ($mj) {
                $majorPart = Str::slug($mj->name, '_');
            }
        }

        $timestamp = date('Ymd_His');
        $filename = "Link_Monitoring_Ortu_{$majorPart}_{$classPart}_{$timestamp}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM untuk kompatibilitas penuh dengan Microsoft Excel pada Windows/Mac
            fputs($file, "\xEF\xBB\xBF");

            // Header Kolom CSV
            fputcsv($file, [
                'No',
                'NISN',
                'NIS',
                'Nama Siswa',
                'Jenis Kelamin',
                'Jurusan',
                'Rombel / Kelas',
                'Tempat PKL (DU/DI)',
                'Guru Pembimbing',
                'Status Portal',
                'Tautan Monitoring Orang Tua',
                'Pesan Siap Kirim WhatsApp',
            ]);

            $no = 1;
            // Gunakan chunking untuk skalabilitas tinggi dengan konsumsi memori minimal
            $query->chunk(150, function ($students) use ($file, &$no) {
                foreach ($students as $student) {
                    $gender = match ($student->user?->profile?->gender) {
                        'L' => 'Laki-Laki',
                        'P' => 'Perempuan',
                        default => '-'
                    };

                    $spkl = $student->activePkl;
                    $companyName = $spkl?->company?->name ?? 'Belum Diplot';
                    $teacherName = $spkl?->teacher?->user?->name ?? 'Belum Ditugaskan';
                    $portalStatus = $student->is_parent_portal_active ? 'Aktif' : 'Nonaktif';
                    $parentUrl = $student->parent_monitoring_url ?? 'Belum Dibuat';
                    $waMessage = $this->buildWhatsAppMessage($student);

                    fputcsv($file, [
                        $no++,
                        $student->nisn ? "\t" . $student->nisn : '-',
                        $student->nis ? "\t" . $student->nis : '-',
                        $student->user?->name ?? '-',
                        $gender,
                        $student->class?->major?->name ?? '-',
                        $student->class?->name ?? '-',
                        $companyName,
                        $teacherName,
                        $portalStatus,
                        $parentUrl,
                        $waMessage,
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

