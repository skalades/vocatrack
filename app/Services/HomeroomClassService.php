<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\IncidentReport;
use App\Models\Journal;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HomeroomClassService
{
    /**
     * Ambil seluruh rombel kelas yang diampu oleh Wali Kelas dalam tenant sekolahnya.
     */
    public function getHomeroomClasses(User $user): Collection
    {
        if (!$user->teacher) {
            return collect();
        }

        return SchoolClass::where('school_id', (int) $user->school_id)
            ->where('homeroom_teacher_id', $user->teacher->id)
            ->with('major')
            ->orderByDesc('academic_year')
            ->orderBy('name')
            ->get();
    }

    /**
     * Selesaikan rombel terpilih (atau seluruh rombel ampuannya) dengan proteksi anti-IDOR.
     *
     * @return array{classes: Collection, selectedClass: ?SchoolClass, targetClassIds: int[]}
     */
    public function resolveScope(User $user, ?int $requestedClassId = null): array
    {
        $classes = $this->getHomeroomClasses($user);
        abort_if($classes->isEmpty(), 403, 'Akses ditolak: Anda belum ditunjuk sebagai Wali Kelas untuk rombel manapun.');

        $selectedClass = null;
        if ($requestedClassId) {
            $selectedClass = $classes->firstWhere('id', $requestedClassId);
            abort_if(!$selectedClass, 403, 'Akses ditolak: Anda bukan Wali Kelas dari rombel yang diminta.');
            $targetClassIds = [(int) $selectedClass->id];
        } else {
            $selectedClass = $classes->first();
            $targetClassIds = $classes->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return [
            'classes' => $classes,
            'selectedClass' => $selectedClass,
            'targetClassIds' => $targetClassIds,
        ];
    }

    /**
     * Data ringkasan dashboard operasional Wali Kelas.
     */
    public function getDashboardData(int $schoolId, array $classIds, User $user): array
    {
        $studentIds = Student::whereIn('class_id', $classIds)
            ->whereHas('user', fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('id');

        $totalStudents = $studentIds->count();

        $activeSpkls = StudentPkl::where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->where('status', 'active')
            ->get();

        $totalPlotted = $activeSpkls->pluck('student_id')->unique()->count();
        $totalUnplotted = max(0, $totalStudents - $totalPlotted);

        $allSpklIds = StudentPkl::where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->pluck('id');

        $today = Carbon::today()->toDateString();
        $todayAttendances = $allSpklIds->isEmpty()
            ? collect()
            : Attendance::where('school_id', $schoolId)
                ->whereIn('student_pkl_id', $allSpklIds)
                ->whereDate('date', $today)
                ->get();

        $hadirToday = $todayAttendances->whereIn('status', ['hadir', 'terlambat'])->count();
        $izinToday = $todayAttendances->whereIn('status', ['izin', 'sakit', 'dinas_luar', 'luar_kantor'])->count();
        $alfaToday = $todayAttendances->where('status', 'alfa')->count();

        $pendingJournalsCount = $allSpklIds->isEmpty()
            ? 0
            : Journal::where('school_id', $schoolId)
                ->whereIn('student_pkl_id', $allSpklIds)
                ->where('status', 'review_guru')
                ->count();

        // Siswa dengan akumulasi alfa tertinggi (peringatan dini Wali Kelas)
        $highAlfaStudents = $allSpklIds->isEmpty()
            ? collect()
            : Attendance::where('school_id', $schoolId)
                ->whereIn('student_pkl_id', $allSpklIds)
                ->where('status', 'alfa')
                ->select('student_pkl_id', DB::raw('COUNT(*) as alfa_count'))
                ->groupBy('student_pkl_id')
                ->havingRaw('COUNT(*) >= 2')
                ->orderByDesc('alfa_count')
                ->with(['studentPkl.student.user', 'studentPkl.student.schoolClass', 'studentPkl.company', 'studentPkl.teacher.user'])
                ->take(5)
                ->get();

        // Daftar siswa yang belum terplotting untuk eskalasi ke Kajur/Admin
        $unplottedStudents = Student::whereIn('class_id', $classIds)
            ->whereHas('user', fn ($q) => $q->where('school_id', $schoolId))
            ->whereDoesntHave('studentPkl', fn ($q) => $q->where('status', 'active'))
            ->with(['user', 'schoolClass'])
            ->take(5)
            ->get();

        // Kendala/insiden terbaru siswa di kelasnya
        $recentIncidents = $allSpklIds->isEmpty()
            ? collect()
            : IncidentReport::where('school_id', $schoolId)
                ->whereIn('student_pkl_id', $allSpklIds)
                ->with(['studentPkl.student.user', 'studentPkl.student.schoolClass', 'studentPkl.company'])
                ->latest()
                ->take(5)
                ->get();

        $openIncidentsCount = $allSpklIds->isEmpty()
            ? 0
            : IncidentReport::where('school_id', $schoolId)
                ->whereIn('student_pkl_id', $allSpklIds)
                ->whereIn('status', ['submitted', 'in_progress'])
                ->count();

        // Informasi multi-peran (jika merangkap Guru Pembimbing atau Kajur)
        $myTeacherId = $user->teacher?->id;
        $myMenteesCount = $myTeacherId
            ? StudentPkl::where('school_id', $schoolId)->where('teacher_id', $myTeacherId)->where('status', 'active')->count()
            : 0;
        $myPendingJournalsCount = $myTeacherId
            ? Journal::where('school_id', $schoolId)
                ->whereHas('studentPkl', fn ($q) => $q->where('teacher_id', $myTeacherId))
                ->where('status', 'review_guru')
                ->count()
            : 0;

        return compact(
            'totalStudents',
            'totalPlotted',
            'totalUnplotted',
            'hadirToday',
            'izinToday',
            'alfaToday',
            'pendingJournalsCount',
            'highAlfaStudents',
            'unplottedStudents',
            'recentIncidents',
            'openIncidentsCount',
            'myMenteesCount',
            'myPendingJournalsCount'
        );
    }

    /**
     * Daftar siswa terpaginasi dalam rombel ampuannya.
     */
    public function getStudents(int $schoolId, array $classIds, array $filters = []): LengthAwarePaginator
    {
        $query = Student::whereIn('class_id', $classIds)
            ->whereHas('user', fn ($q) => $q->where('school_id', $schoolId))
            ->with(['user', 'schoolClass.major', 'activePkl.company', 'activePkl.teacher.user']);

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'plotted') {
                $query->whereHas('studentPkl', fn ($q) => $q->where('status', 'active'));
            } elseif ($filters['status'] === 'unplotted') {
                $query->whereDoesntHave('studentPkl', fn ($q) => $q->where('status', 'active'));
            }
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', $search))
                    ->orWhere('nisn', 'like', $search)
                    ->orWhere('nis', 'like', $search);
            });
        }

        return $query->join('users', 'students.user_id', '=', 'users.id')
            ->orderBy('users.name')
            ->select('students.*')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * Data presensi siswa dalam rombel ampuannya beserta ringkasan KPI.
     *
     * @return array{attendances: LengthAwarePaginator, stats: array}
     */
    public function getAttendances(int $schoolId, array $classIds, array $filters = []): array
    {
        $selectedDate = $filters['date'] ?: Carbon::today()->toDateString();
        $spklIds = $this->getSpklIds($schoolId, $classIds);

        $query = Attendance::where('school_id', $schoolId)
            ->whereIn('student_pkl_id', $spklIds->isEmpty() ? [-1] : $spklIds)
            ->whereDate('date', $selectedDate)
            ->with(['studentPkl.student.user', 'studentPkl.student.schoolClass', 'studentPkl.company', 'studentPkl.teacher.user']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('studentPkl.student.user', fn ($uq) => $uq->where('name', 'like', $search))
                    ->orWhereHas('studentPkl.student', fn ($sq) => $sq->where('nisn', 'like', $search)->orWhere('nis', 'like', $search))
                    ->orWhereHas('studentPkl.company', fn ($cq) => $cq->where('name', 'like', $search));
            });
        }

        $stats = [
            'total' => (clone $query)->count(),
            'hadir' => (clone $query)->where('status', 'hadir')->count(),
            'terlambat' => (clone $query)->where('status', 'terlambat')->count(),
            'izin' => (clone $query)->whereIn('status', ['izin', 'sakit', 'dinas_luar', 'luar_kantor'])->count(),
            'alfa' => (clone $query)->where('status', 'alfa')->count(),
        ];

        $attendances = $query->latest('created_at')->paginate(20)->withQueryString();

        return compact('attendances', 'stats', 'selectedDate');
    }

    /**
     * Data jurnal harian siswa dalam rombel ampuannya.
     */
    public function getJournals(int $schoolId, array $classIds, array $filters = []): LengthAwarePaginator
    {
        $spklIds = $this->getSpklIds($schoolId, $classIds);

        $query = Journal::where('school_id', $schoolId)
            ->whereIn('student_pkl_id', $spklIds->isEmpty() ? [-1] : $spklIds)
            ->with(['studentPkl.student.user', 'studentPkl.student.schoolClass', 'studentPkl.teacher.user']);

        if (!empty($filters['status'])) {
            $statusMap = [
                'pending_teacher' => 'review_guru',
                'pending_dudi' => 'submitted',
                'rejected' => 'revision',
            ];
            $query->where('status', $statusMap[$filters['status']] ?? $filters['status']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('date', $filters['date']);
        }

        return $query->latest('date')->paginate(20)->withQueryString();
    }

    /**
     * Data laporan kendala & SOS siswa dalam rombel ampuannya.
     */
    public function getIncidents(int $schoolId, array $classIds, array $filters = []): LengthAwarePaginator
    {
        $spklIds = $this->getSpklIds($schoolId, $classIds);

        $query = IncidentReport::where('school_id', $schoolId)
            ->whereIn('student_pkl_id', $spklIds->isEmpty() ? [-1] : $spklIds)
            ->with(['studentPkl.student.user', 'studentPkl.student.schoolClass', 'studentPkl.company', 'studentPkl.teacher.user', 'followups.user']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(15)->withQueryString();
    }

    /**
     * Query rekap nilai PKL & sertifikat siswa rombel ampuannya.
     */
    public function getGradesQuery(int $schoolId, array $classIds): Builder
    {
        return StudentPkl::where('school_id', $schoolId)
            ->onlyAssessmentTargets($schoolId)
            ->whereHas('student', fn ($q) => $q->whereIn('class_id', $classIds))
            ->with(['student.user', 'student.schoolClass', 'company', 'teacher.user', 'assessment', 'certificate']);
    }

    /**
     * Ekspor rekap nilai kelas ke CSV (UTF-8 BOM).
     */
    public function exportGradesCsvStream(Builder $query, string $classSlug): StreamedResponse
    {
        $records = (clone $query)->get();
        $filename = 'rekap-nilai-walikelas-' . $classSlug . '-' . date('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['No', 'NISN', 'Nama Siswa', 'Kelas', 'Mitra DU/DI', 'Guru Pembimbing', 'Nilai DU/DI', 'Nilai Guru', 'Nilai Akhir', 'Status']);

            foreach ($records as $idx => $r) {
                $ass = $r->assessment;
                $scoreDudi = $ass ? round(($ass->score_dudi_discipline + $ass->score_dudi_technical + $ass->score_dudi_initiative) / 3, 1) : '-';
                $scoreGuru = $ass ? $ass->score_teacher_report : '-';
                $finalScore = $ass ? $ass->final_score : '-';

                fputcsv($file, [
                    $idx + 1,
                    $r->student?->nisn ? "\t" . $r->student->nisn : '-',
                    $r->student?->user?->name ?? $r->student?->name ?? '-',
                    $r->student?->schoolClass?->name ?? '-',
                    $r->company?->name ?? '-',
                    $r->teacher?->user?->name ?? '-',
                    $scoreDudi,
                    $scoreGuru,
                    $finalScore,
                    $ass ? 'Sudah Dinilai' : 'Belum Lengkap',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function getSpklIds(int $schoolId, array $classIds): Collection
    {
        $studentIds = Student::whereIn('class_id', $classIds)
            ->whereHas('user', fn ($q) => $q->where('school_id', $schoolId))
            ->pluck('id');

        if ($studentIds->isEmpty()) {
            return collect();
        }

        return StudentPkl::where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->pluck('id');
    }
}
