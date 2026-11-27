<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\Journal;
use App\Models\PklPeriod;
use App\Models\SchoolSetting;
use App\Models\StudentPkl;
use App\Models\SupervisionAssignment;
use App\Models\SupervisionVisit;
use App\Services\AnnouncementService;
use App\Services\AssessmentService;
use App\Services\AttendanceService;
use App\Services\AuditLogService;
use App\Services\HolidayService;
use App\Services\ImageCompressionService;
use App\Services\IncidentReportService;
use App\Services\JournalService;
use App\Services\SecureFileService;
use App\Services\SupervisionService;
use App\Services\WhatsAppGatewayService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    protected function getTeacher()
    {
        $teacher = Auth::user()->teacher;
        if (!$teacher) {
            abort(403, 'Profil guru pembimbing tidak ditemukan.');
        }
        return $teacher;
    }

    public function dashboard(
        AnnouncementService $announcementService,
        AttendanceService $attendanceService,
        HolidayService $holidayService,
        SupervisionService $supervisionService
    )
    {
        $teacher = $this->getTeacher();
        $user = Auth::user();
        $now = Carbon::now();
        $today = Carbon::today();
        $todayString = $today->toDateString();

        $studentPkls = StudentPkl::where('teacher_id', $teacher->id)
            ->with(['student.user', 'student.class.major', 'company', 'supervisor.user', 'assessment', 'pklPeriod', 'journals'])
            ->get();

        $spklIds = $studentPkls->pluck('id');
        $totalStudents = $studentPkls->count();
        $companies = $studentPkls->pluck('company')->filter()->unique('id')->values();
        $totalCompanies = $companies->count();

        // ---------- Periode PKL aktif & progres waktu ----------
        $activePeriod = $studentPkls->pluck('pklPeriod')->filter()->unique('id')
            ->sortByDesc(fn ($p) => $p->is_active)->first();
        $periodProgress = null;
        if ($activePeriod && $activePeriod->start_date && $activePeriod->end_date) {
            $totalDays = max(1, $activePeriod->start_date->diffInDays($activePeriod->end_date) + 1);
            $elapsed = $today->lt($activePeriod->start_date) ? 0
                : min($totalDays, $activePeriod->start_date->diffInDays($today) + 1);
            $periodProgress = [
                'name' => $activePeriod->name,
                'start' => $activePeriod->start_date,
                'end' => $activePeriod->end_date,
                'day' => $elapsed,
                'total' => $totalDays,
                'percent' => (int) round($elapsed / $totalDays * 100),
                'remaining' => max(0, $totalDays - $elapsed),
            ];
        }

        // ---------- Presensi hari ini (status per siswa) ----------
        $todayAttendances = Attendance::whereIn('student_pkl_id', $spklIds)
            ->whereDate('date', $todayString)
            ->get()
            ->keyBy('student_pkl_id');

        $todayApprovedRequests = AttendanceRequest::whereIn('student_pkl_id', $spklIds)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $todayString)
            ->whereDate('end_date', '>=', $todayString)
            ->get()
            ->groupBy('student_pkl_id');

        $isSchoolHoliday = $user->school_id ? $holidayService->isHoliday($user->school_id, $today) : false;
        $workHoursCache = [];
        $todayRows = collect();

        foreach ($studentPkls as $sp) {
            $att = $todayAttendances->get($sp->id);
            $company = $sp->company;
            $isWorkDay = true;
            $hours = null;
            if ($company) {
                $isWorkDay = $company->isWorkDay($today);
                $hours = $workHoursCache[$company->id] ??= $attendanceService->resolveWorkHours($company, $sp->school_id);
            }

            $state = 'belum';
            if ($att) {
                $state = $att->status;
            } elseif ($sp->status !== 'active' || !$sp->coversDate($today) || ($company && !$company->is_pkl_started)) {
                $state = 'belum_mulai';
            } elseif ($isSchoolHoliday && !$company?->worksOnHolidays()) {
                $state = 'libur';
            } elseif (!$isWorkDay) {
                $state = 'libur_dudi';
            } elseif ($req = optional($todayApprovedRequests->get($sp->id))->first()) {
                $state = $req->status === 'approved' ? $req->type : 'pengajuan';
            } elseif ($hours && $now->format('H:i:s') > ($hours['checkin_late'] ?? '08:00:00')) {
                $state = 'belum_telat';
            }

            $todayRows->push([
                'spkl' => $sp,
                'attendance' => $att,
                'state' => $state,
                'hours' => $hours,
                'flagged' => $att && ($att->is_mock_detected || $att->is_within_radius === false),
            ]);
        }

        $presentStates = ['hadir', 'terlambat', 'dinas_luar', 'luar_kantor'];
        $todaySummary = [
            'hadir' => $todayRows->where('state', 'hadir')->count(),
            'terlambat' => $todayRows->where('state', 'terlambat')->count(),
            'tugas' => $todayRows->whereIn('state', ['dinas_luar', 'luar_kantor'])->count(),
            'izin_sakit' => $todayRows->whereIn('state', ['izin', 'sakit', 'pengajuan'])->count(),
            'alfa' => $todayRows->where('state', 'alfa')->count(),
            'belum' => $todayRows->whereIn('state', ['belum', 'belum_telat'])->count(),
            'belum_telat' => $todayRows->where('state', 'belum_telat')->count(),
            'libur' => $todayRows->whereIn('state', ['libur', 'libur_dudi'])->count(),
            'belum_mulai' => $todayRows->where('state', 'belum_mulai')->count(),
        ];
        $todayPresentCount = $todayRows->whereIn('state', $presentStates)->count();
        $todayLateCount = $todaySummary['terlambat'];
        $exemptCount = $todaySummary['libur'] + $todaySummary['belum_mulai'];
        $expectedToday = max(0, $totalStudents - $exemptCount);

        // Siswa yang hadir dan sudah tercatat jam pulang valid
        $checkedOutCount = $todayRows->filter(function ($r) use ($presentStates) {
            if (!in_array($r['state'], $presentStates, true)) {
                return false;
            }
            $t = $r['attendance']?->checkout_time;
            return !empty($t) && $t !== '00:00:00';
        })->count();
        $stillAtOfficeCount = max(0, $todayPresentCount - $checkedOutCount);
        $belumMasukCount = $todaySummary['belum'];
        $belumMulaiCount = $todaySummary['belum_mulai'];

        // Urutkan: yang perlu perhatian di atas
        $stateOrder = ['belum_telat' => 0, 'alfa' => 1, 'pengajuan' => 2, 'terlambat' => 3, 'belum' => 4, 'belum_mulai' => 7];
        $todayRows = $todayRows->sortBy(fn ($r) => ($r['flagged'] ? -1 : ($stateOrder[$r['state']] ?? 5)))->values();

        // ---------- Peringatan lokasi (7 hari terakhir) ----------
        $flaggedAttendances = Attendance::whereIn('student_pkl_id', $spklIds)
            ->whereDate('date', '>=', $today->copy()->subDays(6)->toDateString())
            ->where(fn ($q) => $q->where('is_mock_detected', true)->orWhere('is_within_radius', false))
            ->with(['studentPkl.student.user', 'studentPkl.company'])
            ->orderByDesc('date')
            ->take(5)
            ->get();
        $flaggedTodayCount = $todayRows->where('flagged', true)->count();

        // ---------- Tren kehadiran 7 hari ----------
        $trendRaw = Attendance::whereIn('student_pkl_id', $spklIds)
            ->whereDate('date', '>=', $today->copy()->subDays(6)->toDateString())
            ->selectRaw('DATE(date) as d, status, COUNT(*) as c')
            ->groupBy('d', 'status')
            ->get()
            ->groupBy('d');
        $attendanceTrend = collect(range(6, 0))->map(function ($i) use ($today, $trendRaw) {
            $date = $today->copy()->subDays($i);
            $rows = $trendRaw->get($date->toDateString(), collect());
            $sum = fn (array $st) => (int) $rows->whereIn('status', $st)->sum('c');
            return [
                'date' => $date,
                'hadir' => $sum(['hadir', 'dinas_luar', 'luar_kantor']),
                'terlambat' => $sum(['terlambat']),
                'izin' => $sum(['izin', 'sakit']),
                'alfa' => $sum(['alfa']),
            ];
        });

        // ---------- Statistik per siswa (selama periode) ----------
        $attStats = Attendance::whereIn('student_pkl_id', $spklIds)
            ->selectRaw('student_pkl_id, status, COUNT(*) as c')
            ->groupBy('student_pkl_id', 'status')
            ->get()
            ->groupBy('student_pkl_id');
        $journalStats = Journal::whereIn('student_pkl_id', $spklIds)
            ->selectRaw('student_pkl_id, status, COUNT(*) as c, MAX(date) as last_date')
            ->groupBy('student_pkl_id', 'status')
            ->get()
            ->groupBy('student_pkl_id');
        $lastCheckins = Attendance::whereIn('student_pkl_id', $spklIds)
            ->whereNotNull('checkin_time')
            ->selectRaw('student_pkl_id, MAX(date) as last_date')
            ->groupBy('student_pkl_id')
            ->pluck('last_date', 'student_pkl_id');
        $openIncidentsBySpkl = IncidentReport::whereIn('student_pkl_id', $spklIds)
            ->whereIn('status', ['submitted', 'in_progress'])
            ->selectRaw('student_pkl_id, COUNT(*) as c')
            ->groupBy('student_pkl_id')
            ->pluck('c', 'student_pkl_id');

        $studentInsights = $studentPkls->map(function ($sp) use ($attStats, $journalStats, $lastCheckins, $openIncidentsBySpkl, $presentStates, $today, $todayRows) {
            $att = $attStats->get($sp->id, collect());
            $jr = $journalStats->get($sp->id, collect());
            $attCount = fn (array $st) => (int) $att->whereIn('status', $st)->sum('c');
            $jrCount = fn (array $st) => (int) $jr->whereIn('status', $st)->sum('c');

            $totalAtt = (int) $att->sum('c');
            $present = $attCount($presentStates);
            $lastJournal = $jr->max('last_date');
            $daysSinceJournal = $lastJournal ? Carbon::parse($lastJournal)->diffInDays($today) : null;
            $journalTotal = $jrCount(['draft', 'submitted', 'review_guru', 'revision', 'approved']);
            $assessment = $sp->assessment;

            $isPklStarted = (bool) ($sp->company?->is_pkl_started ?? false);
            $warnings = [];
            if ($attCount(['alfa']) >= 3) $warnings[] = $attCount(['alfa']) . 'x alfa';
            if ($isPklStarted) {
                if ($journalTotal === 0) $warnings[] = 'belum ada jurnal';
                elseif ($daysSinceJournal !== null && $daysSinceJournal > 3) $warnings[] = 'jurnal ' . $daysSinceJournal . ' hari kosong';
            }
            if ($openIncidentsBySpkl->get($sp->id)) $warnings[] = 'kendala terbuka';

            return [
                'spkl' => $sp,
                'today' => $todayRows->firstWhere('spkl.id', $sp->id),
                'attendance_rate' => $totalAtt > 0 ? (int) round($present / $totalAtt * 100) : null,
                'present' => $present,
                'late' => $attCount(['terlambat']),
                'leave' => $attCount(['izin', 'sakit']),
                'alfa' => $attCount(['alfa']),
                'last_checkin' => $lastCheckins->get($sp->id) ? Carbon::parse($lastCheckins->get($sp->id)) : null,
                'journal_total' => $journalTotal,
                'journal_approved' => $jrCount(['approved']),
                'journal_review_guru' => $sp->journals->filter(function($j) use ($sp) {
                    return ($j->status === 'review_guru') || ($j->status === 'submitted' && !$sp->company->requires_industry_approval);
                })->count(),
                'journal_at_dudi' => $sp->journals->filter(function($j) use ($sp) {
                    return ($j->status === 'submitted' && $sp->company->requires_industry_approval);
                })->count(),
                'journal_revision' => $jrCount(['revision']),
                'days_since_journal' => $daysSinceJournal,
                'teacher_scored' => $assessment && $assessment->score_teacher_report !== null,
                'dudi_scored' => $assessment && $assessment->score_dudi_technical !== null,
                'final_score' => $assessment?->final_score,
                'open_incidents' => (int) $openIncidentsBySpkl->get($sp->id, 0),
                'warnings' => $warnings,
            ];
        })->sortByDesc(fn ($s) => count($s['warnings']))->values();

        $ratedStudents = $studentInsights->whereNotNull('attendance_rate');
        $avgAttendanceRate = $ratedStudents->count() ? (int) round($ratedStudents->avg('attendance_rate')) : null;
        $studentsNeedAttention = $studentInsights->filter(fn ($s) => count($s['warnings']) > 0)->count();

        // ---------- Antrean verifikasi ----------
        $pendingRequestsList = AttendanceRequest::whereIn('student_pkl_id', $spklIds)
            ->whereIn('type', ['izin', 'sakit'])
            ->where('status', 'pending')
            ->with(['studentPkl.student.user', 'studentPkl.company'])
            ->oldest()
            ->take(4)
            ->get();
        $pendingRequests = AttendanceRequest::whereIn('student_pkl_id', $spklIds)
            ->whereIn('type', ['izin', 'sakit'])
            ->where('status', 'pending')
            ->count();

        $pendingJournalsList = Journal::whereIn('student_pkl_id', $spklIds)
            ->where(function($q) {
                $q->where('status', 'review_guru')
                  ->orWhere(function($sub) {
                      $sub->where('status', 'submitted')
                          ->whereHas('studentPkl.company', fn($c) => $c->where('requires_industry_approval', false));
                  });
            })
            ->with(['studentPkl.student.user', 'studentPkl.company'])
            ->oldest('date')
            ->take(4)
            ->get();
        $pendingJournals = Journal::whereIn('student_pkl_id', $spklIds)
            ->where(function($q) {
                $q->where('status', 'review_guru')
                  ->orWhere(function($sub) {
                      $sub->where('status', 'submitted')
                          ->whereHas('studentPkl.company', fn($c) => $c->where('requires_industry_approval', false));
                  });
            })
            ->count();

        // Jurnal tertahan di DU/DI > 2 hari (perlu didorong ke pembimbing industri)
        $stuckAtDudi = Journal::whereIn('student_pkl_id', $spklIds)
            ->where('status', 'submitted')
            ->whereHas('studentPkl.company', fn($c) => $c->where('requires_industry_approval', true))
            ->where('updated_at', '<', $now->copy()->subDays(2))
            ->with(['studentPkl.company', 'studentPkl.supervisor.user'])
            ->get()
            ->groupBy(fn ($j) => $j->studentPkl->company_id)
            ->map(fn ($group) => [
                'company' => $group->first()->studentPkl->company,
                'supervisor' => $group->first()->studentPkl->supervisor?->user?->name,
                'count' => $group->count(),
                'oldest' => $group->min('date'),
            ])
            ->values();

        // ---------- Kendala & SOS ----------
        $openIncidents = IncidentReport::whereIn('student_pkl_id', $spklIds)
            ->whereIn('status', ['submitted', 'in_progress'])
            ->with(['studentPkl.student.user', 'studentPkl.company'])
            ->orderByRaw("CASE WHEN urgency = 'critical' THEN 1 WHEN urgency = 'high' THEN 2 WHEN urgency = 'medium' THEN 3 ELSE 4 END")
            ->latest()
            ->take(4)
            ->get();
        $openIncidentsCount = IncidentReport::whereIn('student_pkl_id', $spklIds)
            ->whereIn('status', ['submitted', 'in_progress'])
            ->count();
        $criticalIncidentsCount = IncidentReport::whereIn('student_pkl_id', $spklIds)
            ->whereIn('status', ['submitted', 'in_progress'])
            ->where('urgency', 'critical')
            ->count();

        // ---------- Supervisi 2x Monitoring per DU/DI ----------
        $supervisionData = $supervisionService->getDashboardProgressForTeacher($teacher, $companies, $studentPkls);
        $supervisionStages = $supervisionData['supervisionStages'];
        $supervisionProgress = $supervisionData['supervisionProgress'];
        $supervisionDone = $supervisionData['supervisionDone'];
        $supervisionTarget = $supervisionData['supervisionTarget'];

        // ---------- Penilaian ----------
        $teacherScoredCount = $studentInsights->where('teacher_scored', true)->count();
        $finalScores = $studentInsights->pluck('final_score')->filter();
        $avgFinalScore = $finalScores->count() ? round($finalScores->avg(), 1) : null;

        $recentAnnouncements = $announcementService->getLatestForUser($user, 3);

        $pendingResetRequests = \App\Models\StudentPasswordResetRequest::where('teacher_id', $teacher->id)
            ->where('status', 'pending')
            ->with('student.user')
            ->latest()
            ->get();

        return view('teacher.dashboard', compact(
            'teacher',
            'totalStudents',
            'totalCompanies',
            'periodProgress',
            'isSchoolHoliday',
            'todayRows',
            'todaySummary',
            'todayPresentCount',
            'todayLateCount',
            'expectedToday',
            'checkedOutCount',
            'stillAtOfficeCount',
            'belumMasukCount',
            'belumMulaiCount',
            'flaggedAttendances',
            'flaggedTodayCount',
            'attendanceTrend',
            'studentInsights',
            'avgAttendanceRate',
            'studentsNeedAttention',
            'pendingRequests',
            'pendingRequestsList',
            'pendingJournals',
            'pendingJournalsList',
            'stuckAtDudi',
            'openIncidents',
            'openIncidentsCount',
            'criticalIncidentsCount',
            'supervisionStages',
            'supervisionProgress',
            'supervisionDone',
            'supervisionTarget',
            'teacherScoredCount',
            'avgFinalScore',
            'studentPkls',
            'recentAnnouncements',
            'pendingResetRequests'
        ));
    }

    public function resetStudentPassword(Request $request, \App\Models\Student $student)
    {
        $teacher = $this->getTeacher();
        $assignment = StudentPkl::where('teacher_id', $teacher->id)
            ->where('student_id', $student->id)
            ->whereIn('status', ['assigned', 'active'])
            ->where('school_id', Auth::user()->school_id)
            ->firstOrFail();

        abort_unless($student->user?->role === 'siswa', 403, 'Akun target bukan akun siswa.');

        $validated = $request->validate([
            'new_password' => ['nullable', 'string', 'min:8', 'max:100'],
        ], [
            'new_password.min' => 'Kata sandi baru minimal 8 karakter. Kosongkan untuk dibuatkan otomatis.',
        ]);

        $user = $student->user;
        $newPassword = $validated['new_password'] ?? \App\Services\TenantProvisioningService::generateTemporaryPassword();

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $newPassword, $teacher, $assignment, $student) {
            $user->update([
                'password' => $newPassword,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            if (config('session.driver') === 'database') {
                \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }

            \App\Models\StudentPasswordResetRequest::where('student_id', $student->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'completed',
                    'notes' => "Direset oleh guru pembimbing {$teacher->user->name} pada " . now()->format('d/m/Y H:i'),
                ]);

            \App\Services\AuditLogService::log(
                'password_reset',
                'security',
                "Kata sandi siswa ({$user->email}) direset oleh guru pembimbing " . Auth::user()->email . '.',
                null,
                Auth::user(),
                null,
                ['target_user_id' => $user->id, 'student_pkl_id' => $assignment->id, 'teacher_id' => $teacher->id]
            );
        });

        return back()
            ->with('success', "Kata sandi {$user->name} berhasil direset. Siswa wajib menggantinya saat login berikutnya.")
            ->with('temporary_credential', [
                'email' => $user->email,
                'password' => $newPassword,
                'context' => "Siswa Bimbingan {$user->name}",
            ]);
    }
    public function monitoring(Request $request)
    {
        $teacher = $this->getTeacher();
        $spklIds = StudentPkl::where('teacher_id', $teacher->id)->pluck('id');

        $query = Attendance::whereIn('student_pkl_id', $spklIds)
            ->with(['studentPkl.student.user', 'studentPkl.company'])
            ->orderBy('date', 'desc');

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->paginate(20)->withQueryString();

        $baseStatsQuery = Attendance::whereIn('student_pkl_id', $spklIds);
        if ($request->filled('date')) {
            $baseStatsQuery->where('date', $request->date);
        }
        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'hadir' => (clone $baseStatsQuery)->where('status', 'hadir')->count(),
            'late' => (clone $baseStatsQuery)->where('status', 'terlambat')->count(),
            'leave' => (clone $baseStatsQuery)->whereIn('status', ['izin', 'sakit', 'dinas_luar', 'luar_kantor'])->count(),
        ];

        $pendingResetRequests = \App\Models\StudentPasswordResetRequest::where('teacher_id', $teacher->id)
            ->where('status', 'pending')
            ->with('student.user')
            ->latest()
            ->get();

        $myStudents = StudentPkl::where('teacher_id', $teacher->id)
            ->whereIn('status', ['assigned', 'active'])
            ->with(['student.user', 'company'])
            ->get();

        return view('teacher.monitoring', compact('attendances', 'stats', 'pendingResetRequests', 'myStudents'));
    }

    public function overrideAttendance(Request $request, AttendanceService $attendanceService)
    {
        $teacher = $this->getTeacher();

        $request->validate([
            'student_pkl_id' => ['required', 'integer'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', 'in:hadir,terlambat,izin,sakit,dinas_luar,luar_kantor,alfa'],
            'checkin_time' => ['nullable', 'string', 'max:8'],
            'checkout_time' => ['nullable', 'string', 'max:8'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'student_pkl_id.required' => 'Pilih siswa binaan terlebih dahulu.',
            'date.before_or_equal' => 'Tanggal presensi tidak boleh melebihi hari ini.',
            'reason.required' => 'Wajib mencantumkan alasan override (misal: kendala HP siswa rusak).',
            'reason.min' => 'Alasan override minimal 5 karakter.',
        ]);

        $spkl = StudentPkl::where('id', $request->student_pkl_id)
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $attendanceService->overrideAttendanceByTeacher($spkl, $teacher, $request->all());

        return back()->with('success', "Presensi {$spkl->student->user->name} tanggal " . Carbon::parse($request->date)->format('d/m/Y') . " berhasil di-override menjadi " . strtoupper(str_replace('_', ' ', $request->status)) . ".");
    }

    public function requests()
    {
        $teacher = $this->getTeacher();
        $spklIds = StudentPkl::where('teacher_id', $teacher->id)->pluck('id');

        $requests = AttendanceRequest::whereIn('student_pkl_id', $spklIds)
            ->whereIn('type', ['izin', 'sakit'])
            ->with(['studentPkl.student.user', 'studentPkl.company'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $baseStatsQuery = AttendanceRequest::whereIn('student_pkl_id', $spklIds)->whereIn('type', ['izin', 'sakit']);
        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'pending' => (clone $baseStatsQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseStatsQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $baseStatsQuery)->where('status', 'rejected')->count(),
        ];

        return view('teacher.requests', compact('requests', 'stats'));
    }

    public function processRequest(Request $request, AttendanceRequest $attRequest, AttendanceService $attendanceService)
    {
        $teacher = $this->getTeacher();
        if ($attRequest->studentPkl->teacher_id !== $teacher->id) {
            abort(403, 'Akses ditolak: Anda bukan guru pembimbing yang ditugaskan untuk siswa ini.');
        }

        $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'reviewer_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $attendanceService->processRequest($attRequest, $request->action, $request->reviewer_notes, Auth::user());

        $actionText = ($request->action === 'approve') ? 'disetujui' : 'ditolak';
        return back()->with('success', "Permohonan presensi siswa berhasil {$actionText}.");
    }

    public function bulkProcessRequests(Request $request, AttendanceService $attendanceService)
    {
        $request->validate([
            'request_ids' => ['required', 'array', 'min:1'],
            'request_ids.*' => ['integer', 'exists:attendance_requests,id'],
            'action' => ['required', 'in:approve,reject'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $attendanceService->bulkProcessRequests(
            $request->request_ids,
            $request->action,
            $request->notes,
            Auth::user()
        );

        $actionText = ($request->action === 'approve') ? 'disetujui' : 'ditolak';
        return back()->with('success', "Berhasil memproses massal: {$result['processed']} permohonan telah {$actionText} (Dilewati: {$result['skipped']}).");
    }

    public function journals(Request $request)
    {
        $teacher = $this->getTeacher();
        $studentPkls = StudentPkl::where('teacher_id', $teacher->id)
            ->with([
                'student.user', 
                'student.class.major', 
                'company',
                'journals' => fn($q) => $q->orderBy('date', 'desc')->with(['photos', 'approvals.approver'])
            ])
            ->get();
        $spklIds = $studentPkls->pluck('id');

        $query = Journal::whereIn('student_pkl_id', $spklIds)
            ->with(['studentPkl.student.user', 'studentPkl.student.class', 'studentPkl.company', 'photos', 'approvals.approver', 'learningObjectives']);

        if ($request->filled('student_pkl_id')) {
            $query->where('student_pkl_id', $request->student_pkl_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('activity_description', 'like', "%{$search}%")
                  ->orWhereHas('studentPkl.student.user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $journals = $query->orderByRaw("CASE WHEN status = 'review_guru' THEN 1 WHEN status = 'submitted' THEN 2 ELSE 3 END")
            ->orderBy('date', 'desc')
            ->paginate(20)
            ->withQueryString();

        $baseStatsQuery = Journal::whereIn('student_pkl_id', $spklIds);
        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'pending_guru' => (clone $baseStatsQuery)->where(function($q) {
                $q->where('status', 'review_guru')
                  ->orWhere(function($sub) {
                      $sub->where('status', 'submitted')
                          ->whereHas('studentPkl.company', fn($c) => $c->where('requires_industry_approval', false));
                  });
            })->count(),
            'approved' => (clone $baseStatsQuery)->where('status', 'approved')->count(),
            'revision' => (clone $baseStatsQuery)->where('status', 'revision')->count(),
        ];

        return view('teacher.journals', compact('journals', 'studentPkls', 'stats'));
    }

    public function processJournal(Request $request, Journal $journal, JournalService $journalService)
    {
        $teacher = $this->getTeacher();
        if ($journal->studentPkl->teacher_id !== $teacher->id) {
            abort(403, 'Akses ditolak: Anda bukan guru pembimbing yang ditugaskan untuk siswa ini.');
        }

        $request->validate([
            'action' => ['required', 'in:approve,revision'],
            'notes' => ['nullable', 'string', 'max:500'],
            'tab' => ['nullable', 'string', 'in:students,pending,table'],
        ]);

        $activeTab = $request->input('tab', 'pending');

        try {
            if ($request->action === 'approve') {
                $journalService->approve($journal, Auth::user(), $request->notes);
                return back()->with('success', 'Jurnal berhasil disetujui (Status Final Approved).')->with('active_tab', $activeTab);
            } else {
                if (empty($request->notes)) {
                    return back()->withErrors(['notes' => 'Catatan wajib diisi saat meminta revisi jurnal.'])->with('active_tab', $activeTab);
                }
                $journalService->requestRevision($journal, Auth::user(), $request->notes);
                return back()->with('success', 'Catatan revisi berhasil dikirimkan ke siswa.')->with('active_tab', $activeTab);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['status' => $e->getMessage()])->with('active_tab', $activeTab);
        }
    }

    public function bulkProcessJournals(Request $request, JournalService $journalService)
    {
        $request->validate([
            'journal_ids' => ['required', 'array', 'min:1'],
            'journal_ids.*' => ['integer', 'exists:journals,id'],
            'action' => ['required', 'in:approve,revision'],
            'notes' => ['nullable', 'string', 'max:500'],
            'tab' => ['nullable', 'string', 'in:students,pending,table'],
        ]);

        $activeTab = $request->input('tab', 'pending');

        if ($request->action === 'revision' && empty($request->notes)) {
            return back()->withErrors(['notes' => 'Catatan revisi wajib diisi untuk revisi massal.'])->with('active_tab', $activeTab);
        }

        try {
            $result = $journalService->bulkProcess(
                $request->journal_ids,
                $request->action,
                $request->notes,
                Auth::user()
            );

            $actionText = ($request->action === 'approve') ? 'disetujui' : 'diminta revisi';
            return back()->with('success', "Berhasil memproses massal: {$result['processed']} jurnal telah {$actionText} (Dilewati: {$result['skipped']}).")->with('active_tab', $activeTab);
        } catch (\Exception $e) {
            return back()->withErrors(['status' => $e->getMessage()])->with('active_tab', $activeTab);
        }
    }

    public function assessments()
    {
        $teacher = $this->getTeacher();
        $studentPkls = StudentPkl::where('teacher_id', $teacher->id)
            ->with(['student.user', 'student.class.major', 'company', 'assessment', 'pklPeriod'])
            ->get()
            ->filter(fn (StudentPkl $sp) => $sp->isAssessmentTarget())
            ->values();

        $weightDudi = (float) (\App\Models\SchoolSetting::where('school_id', $teacher->school_id)->where('key', 'weight_dudi')->value('value') ?? 60);
        $weightGuru = (float) (\App\Models\SchoolSetting::where('school_id', $teacher->school_id)->where('key', 'weight_guru')->value('value') ?? 40);

        return view('teacher.assessments', compact('studentPkls', 'weightDudi', 'weightGuru'));
    }

    public function storeAssessment(Request $request, AssessmentService $assessmentService)
    {
        $teacher = $this->getTeacher();

        $request->validate([
            'student_pkl_id' => ['required', 'exists:student_pkl,id'],
            'score_teacher_report' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes_teacher' => ['nullable', 'string'],
        ]);

        $spkl = StudentPkl::findOrFail($request->student_pkl_id);

        if ($spkl->teacher_id !== $teacher->id) {
            abort(403, 'Akses ditolak: Anda bukan guru pembimbing yang ditugaskan untuk siswa ini.');
        }

        // Thread-safe and atomic assessment scoring
        $assessmentService->saveTeacherAssessment($spkl, [
            'score_teacher_report' => $request->score_teacher_report,
            'notes_teacher' => $request->notes_teacher,
        ]);

        return back()->with('success', 'Nilai pembimbingan dan laporan sekolah berhasil disimpan. Nilai akhir telah dikalkulasi.');
    }

    public function reports()
    {
        $teacher = $this->getTeacher();
        $studentPkls = StudentPkl::where('teacher_id', $teacher->id)
            ->with(['student.user', 'student.class.major', 'company', 'assessment'])
            ->withCount([
                'attendances',
                'journals as approved_journals_count' => fn($q) => $q->where('status', 'approved')
            ])
            ->get();

        return view('teacher.reports', compact('studentPkls'));
    }

    public function downloadPdf(Request $request, StudentPkl $spkl)
    {
        $teacher = $this->getTeacher();
        if ($spkl->teacher_id !== $teacher->id) {
            abort(403);
        }

        $includePhotos = $request->boolean('include_photos', false);

        $journalRelations = ['learningObjectives'];
        if ($includePhotos) {
            $journalRelations[] = 'photos';
        }

        $spkl->load([
            'school',
            'student.user',
            'student.class.major',
            'company',
            'supervisor.user',
            'teacher.user',
            'pklPeriod',
            'attendances' => fn($q) => $q->orderBy('date', 'asc'),
            'journals' => fn($q) => $q->where('status', 'approved')->with($journalRelations)->orderBy('date', 'asc'),
            'assessment',
        ]);

        $settings = SchoolSetting::where('school_id', $spkl->school_id)->pluck('value', 'key');

        $schoolLogoBase64 = null;
        if ($spkl->school->logo_path && Storage::disk('public')->exists($spkl->school->logo_path)) {
            $mime = Storage::disk('public')->mimeType($spkl->school->logo_path) ?? 'image/png';
            $content = base64_encode(Storage::disk('public')->get($spkl->school->logo_path));
            $schoolLogoBase64 = "data:{$mime};base64,{$content}";
        }

        if ($includePhotos) {
            ini_set('memory_limit', '512M');
            set_time_limit(180);

            $secureService = app(SecureFileService::class);
            $compressor = app(ImageCompressionService::class);
            foreach ($spkl->journals as $journal) {
                foreach ($journal->photos as $photo) {
                    $disk = $secureService->diskFor($photo->photo_path);
                    if ($disk && Storage::disk($disk)->exists($photo->photo_path)) {
                        $raw = Storage::disk($disk)->get($photo->photo_path);
                        $mime = Storage::disk($disk)->mimeType($photo->photo_path) ?? 'image/jpeg';
                        $ext = pathinfo($photo->photo_path, PATHINFO_EXTENSION);

                        if ($compressor->isHeic($raw, $ext, $mime)) {
                            $jpeg = $compressor->decodeHeicToJpeg($raw);
                            if ($jpeg !== null) {
                                $raw = $jpeg;
                                $mime = 'image/jpeg';
                            } else {
                                $photo->base64_data = null;
                                continue;
                            }
                        }

                        $photo->base64_data = 'data:' . $mime . ';base64,' . base64_encode($raw);
                    } else {
                        $photo->base64_data = null;
                    }
                }
            }
        }

        $pdf = Pdf::loadView('reports.student_pdf', compact('spkl', 'settings', 'schoolLogoBase64', 'includePhotos'));
        $pdf->setPaper('a4', 'portrait');

        $sanitizedName = Str::slug($spkl->student->user->name);
        $suffix = $includePhotos ? '_Lengkap_Foto' : '';
        return $pdf->download("Laporan_PKL_{$sanitizedName}{$suffix}.pdf");
    }

    public function incidents(Request $request, IncidentReportService $incidentService)
    {
        $teacher = $this->getTeacher();
        $incidents = $incidentService->getIncidentsForTeacher($teacher, $request->only(['status', 'urgency']));

        $spklIds = StudentPkl::where('teacher_id', $teacher->id)->pluck('id');
        $stats = [
            'total' => IncidentReport::whereIn('student_pkl_id', $spklIds)->count(),
            'open' => IncidentReport::whereIn('student_pkl_id', $spklIds)->whereIn('status', ['submitted', 'in_progress'])->count(),
            'critical' => IncidentReport::whereIn('student_pkl_id', $spklIds)->where('urgency', 'critical')->count(),
            'resolved' => IncidentReport::whereIn('student_pkl_id', $spklIds)->where('status', 'resolved')->count(),
        ];

        return view('teacher.incidents', compact('incidents', 'stats'));
    }

    public function followupIncident(Request $request, IncidentReport $report, IncidentReportService $incidentService)
    {
        $teacher = $this->getTeacher();
        if ($report->studentPkl->teacher_id !== $teacher->id) {
            abort(403, 'Akses ditolak: Anda bukan guru pembimbing yang ditugaskan untuk siswa ini.');
        }

        $validated = $request->validate([
            'action_note' => ['required', 'string', 'max:1000'],
            'status' => ['nullable', 'in:in_progress,resolved,dismissed'],
        ]);

        $incidentService->addFollowup($report, Auth::user(), $validated['action_note'], $validated['status'] ?? null);

        return back()->with('success', 'Tindak lanjut laporan kendala berhasil dicatat.');
    }

    public function supervisions(Request $request, SupervisionService $supervisionService)
    {
        $teacher = $this->getTeacher();
        $assignedTargets = $supervisionService->getAssignedTargetsForTeacher($teacher);
        $visits = $supervisionService->getVisitsForTeacher(
            $teacher,
            $request->only(['company_id', 'stage', 'role_scope'])
        );

        $formOptions = $supervisionService->getVisitFormOptionsForTeacher($teacher);
        $companies = $formOptions['companies'];
        $periods = $formOptions['periods'];

        $filterCompanies = $companies
            ->concat($visits->getCollection()->pluck('company'))
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $pendingAssignedTargets = $assignedTargets->where('is_completed', false)->values();
        $completedAssignedTargets = $assignedTargets->where('is_completed', true)->values();

        $stats = [
            'pending_tasks' => $pendingAssignedTargets->count(),
            'total' => $visits->total(),
            'this_month' => SupervisionVisit::where('school_id', Auth::user()->school_id)
                ->where(function ($q) use ($teacher) {
                    $q->where('teacher_id', $teacher->id)
                        ->orWhereHas('studentPkls', fn ($sq) => $sq->where('student_pkl.teacher_id', $teacher->id));
                })
                ->whereYear('visit_date', now()->year)
                ->whereMonth('visit_date', now()->month)
                ->count(),
            'companies_count' => $filterCompanies->count(),
        ];

        return view('teacher.supervisions', compact(
            'teacher',
            'visits',
            'companies',
            'filterCompanies',
            'periods',
            'stats',
            'pendingAssignedTargets',
            'completedAssignedTargets'
        ));
    }

    /**
     * Halaman briefing read-only siswa untuk petugas monitoring (otomatis tertutup saat laporan selesai).
     */
    public function monitoringBriefing(SupervisionAssignment $assignment, Company $company, SupervisionService $supervisionService)
    {
        $teacher = $this->getTeacher();
        $briefing = $supervisionService->getReadOnlyBriefingForAssignmentCompany($teacher, $assignment, $company);

        if (!$briefing) {
            return redirect()->route('guru.supervisions')
                ->with('error', 'Akses monitoring read-only telah ditutup karena laporan kunjungan untuk perusahaan ini sudah selesai atau tidak ditemukan.');
        }

        return view('teacher.supervision-briefing', compact('teacher', 'briefing'));
    }

    public function storeSupervision(Request $request, SupervisionService $supervisionService)
    {
        $teacher = $this->getTeacher();
        $schoolId = Auth::user()->school_id;

        $validated = $request->validate([
            'company_id' => [
                'required',
                Rule::exists('companies', 'id')->where('school_id', $schoolId),
            ],
            'pkl_period_id' => [
                'required',
                Rule::exists('pkl_periods', 'id')->where('school_id', $schoolId),
            ],
            'supervision_assignment_id' => ['nullable', 'integer'],
            'student_pkl_ids' => ['nullable', 'array'],
            'student_pkl_ids.*' => ['integer'],
            'stage' => ['required', 'in:penyerahan,monitoring_1,monitoring_2,penjemputan,insidental'],
            'visit_date' => ['required', 'date'],
            'student_progress_summary' => ['required', 'string', 'min:10'],
            'company_feedback' => ['required', 'string', 'min:10'],
            'follow_up_agreement' => ['nullable', 'string'],
            'photo_with_students' => ['nullable', 'image', 'max:4096'],
            'photo_with_supervisor' => ['nullable', 'image', 'max:4096'],
        ]);

        $supervisionService->createVisit(
            $teacher,
            $validated,
            $request->file('photo_with_students'),
            $request->file('photo_with_supervisor')
        );

        return redirect()->route('guru.supervisions')->with('success', 'Log kunjungan supervisi berhasil dicatat dan Berita Acara siap diunduh.');
    }

    public function downloadBeritaAcaraPdf(SupervisionVisit $supervisionVisit, SupervisionService $supervisionService)
    {
        $this->getTeacher();

        if (!$supervisionService->canViewVisit(Auth::user(), $supervisionVisit)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mengunduh dokumen Berita Acara ini.');
        }

        try {
            $pdf = $supervisionService->generateBeritaAcaraPdf($supervisionVisit);

            return $pdf->download($supervisionService->buildBeritaAcaraFilename($supervisionVisit));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Download Berita Acara PDF gagal (Visit ID: {$supervisionVisit->id}): " . $e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->with('error', 'Gagal memproses pengunduhan dokumen Berita Acara PDF: ' . $e->getMessage());
        }
    }

    /**
     * Pembaruan profil mandiri oleh Guru Pembimbing (Nama, NIP, dan Nomor HP/WhatsApp).
     */
    public function updateProfile(Request $request)
    {
        $teacher = $this->getTeacher();
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'nip' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:25'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama lengkap maksimal 100 karakter.',
            'nip.max' => 'NIP maksimal 30 karakter.',
            'phone.max' => 'Nomor HP/WhatsApp maksimal 25 karakter.',
        ]);

        $normalizedPhone = null;
        if (filled($validated['phone'] ?? null)) {
            $normalizedPhone = WhatsAppGatewayService::normalizePhoneNumber($validated['phone']);
            if (! WhatsAppGatewayService::isValidIndonesianNumber($normalizedPhone)) {
                return back()
                    ->withErrors(['phone' => 'Format nomor HP/WhatsApp tidak valid. Gunakan format 08xx atau 628xx (minimal 10 digit).'])
                    ->withInput();
            }
        }

        $nip = filled($validated['nip'] ?? null) ? trim($validated['nip']) : null;

        DB::transaction(function () use ($user, $teacher, $validated, $nip, $normalizedPhone) {
            $user->update([
                'name' => trim($validated['name']),
                'wa_number' => $normalizedPhone,
            ]);

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                ['phone' => $normalizedPhone]
            );

            $teacher->update([
                'nip' => $nip,
            ]);
        });

        AuditLogService::log(
            action: 'teacher_profile_updated',
            module: 'profile',
            description: "Guru pembimbing {$user->name} memperbarui profil mandiri (NIP/Nama/Nomor HP)."
        );

        return back()->with('success', 'Profil guru pembimbing berhasil diperbarui.');
    }
}
