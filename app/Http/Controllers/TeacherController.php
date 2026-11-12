<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\IncidentReport;
use App\Models\Journal;
use App\Models\PklPeriod;
use App\Models\SchoolSetting;
use App\Models\StudentPkl;
use App\Models\SupervisionVisit;
use App\Services\AnnouncementService;
use App\Services\AssessmentService;
use App\Services\AttendanceService;
use App\Services\IncidentReportService;
use App\Services\JournalService;
use App\Services\SupervisionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function dashboard(AnnouncementService $announcementService)
    {
        $teacher = $this->getTeacher();
        $studentPkls = StudentPkl::where('teacher_id', $teacher->id)
            ->with(['student.user', 'student.class.major', 'company', 'supervisor.user', 'assessment'])
            ->withCount([
                'attendances',
                'journals as approved_journals_count' => fn($q) => $q->where('status', 'approved')
            ])
            ->get();
            
        $totalStudents = $studentPkls->count();
        $totalCompanies = $studentPkls->pluck('company_id')->unique()->count();

        $today = Carbon::today()->toDateString();
        $spklIds = $studentPkls->pluck('id');

        $todayAttendances = Attendance::whereIn('student_pkl_id', $spklIds)
            ->where('date', $today)
            ->get()
            ->keyBy('student_pkl_id');

        $todayPresentCount = $todayAttendances->whereIn('status', ['hadir', 'terlambat', 'dinas_luar', 'luar_kantor'])->count();
        $todayLateCount = $todayAttendances->where('status', 'terlambat')->count();

        $pendingRequestsList = AttendanceRequest::whereIn('student_pkl_id', $spklIds)
            ->whereIn('type', ['izin', 'sakit'])
            ->where('status', 'pending')
            ->with(['studentPkl.student.user', 'studentPkl.company'])
            ->latest()
            ->take(3)
            ->get();

        $pendingRequests = AttendanceRequest::whereIn('student_pkl_id', $spklIds)
            ->whereIn('type', ['izin', 'sakit'])
            ->where('status', 'pending')
            ->count();

        $pendingJournalsList = Journal::whereIn('student_pkl_id', $spklIds)
            ->where('status', 'review_guru')
            ->with(['studentPkl.student.user', 'studentPkl.company'])
            ->latest('date')
            ->take(3)
            ->get();

        $pendingJournals = Journal::whereIn('student_pkl_id', $spklIds)
            ->where('status', 'review_guru')
            ->count();

        $recentAnnouncements = $announcementService->getLatestForUser(Auth::user(), 4);

        return view('teacher.dashboard', compact(
            'teacher',
            'totalStudents',
            'totalCompanies',
            'todayAttendances',
            'todayPresentCount',
            'todayLateCount',
            'pendingRequests',
            'pendingRequestsList',
            'pendingJournals',
            'pendingJournalsList',
            'studentPkls',
            'recentAnnouncements'
        ));
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

        return view('teacher.monitoring', compact('attendances', 'stats'));
    }

    public function requests()
    {
        $teacher = $this->getTeacher();
        $spklIds = StudentPkl::where('teacher_id', $teacher->id)->pluck('id');

        $requests = AttendanceRequest::whereIn('student_pkl_id', $spklIds)
            ->whereIn('type', ['izin', 'sakit'])
            ->with(['studentPkl.student.user', 'studentPkl.company'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

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
            ->with(['studentPkl.student.user', 'studentPkl.student.class', 'studentPkl.company', 'photos', 'approvals.approver']);

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

        $journals = $query->orderByRaw("CASE WHEN status = 'review_guru' THEN 1 ELSE 2 END")
            ->orderBy('date', 'desc')
            ->paginate(15)
            ->withQueryString();

        $baseStatsQuery = Journal::whereIn('student_pkl_id', $spklIds);
        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'pending_guru' => (clone $baseStatsQuery)->where('status', 'review_guru')->count(),
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
        ]);

        try {
            if ($request->action === 'approve') {
                $journalService->approve($journal, Auth::user(), $request->notes);
                return back()->with('success', 'Jurnal berhasil disetujui (Status Final Approved).');
            } else {
                if (empty($request->notes)) {
                    return back()->withErrors(['notes' => 'Catatan wajib diisi saat meminta revisi jurnal.']);
                }
                $journalService->requestRevision($journal, Auth::user(), $request->notes);
                return back()->with('success', 'Catatan revisi berhasil dikirimkan ke siswa.');
            }
        } catch (\Exception $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    public function bulkProcessJournals(Request $request, JournalService $journalService)
    {
        $request->validate([
            'journal_ids' => ['required', 'array', 'min:1'],
            'journal_ids.*' => ['integer', 'exists:journals,id'],
            'action' => ['required', 'in:approve,revision'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->action === 'revision' && empty($request->notes)) {
            return back()->withErrors(['notes' => 'Catatan revisi wajib diisi untuk revisi massal.']);
        }

        try {
            $result = $journalService->bulkProcess(
                $request->journal_ids,
                $request->action,
                $request->notes,
                Auth::user()
            );

            $actionText = ($request->action === 'approve') ? 'disetujui' : 'diminta revisi';
            return back()->with('success', "Berhasil memproses massal: {$result['processed']} jurnal telah {$actionText} (Dilewati: {$result['skipped']}).");
        } catch (\Exception $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }

    public function assessments()
    {
        $teacher = $this->getTeacher();
        $studentPkls = StudentPkl::where('teacher_id', $teacher->id)
            ->with(['student.user', 'student.class.major', 'company', 'assessment'])
            ->get();

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

    public function downloadPdf(StudentPkl $spkl)
    {
        $teacher = $this->getTeacher();
        if ($spkl->teacher_id !== $teacher->id) {
            abort(403);
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
            'journals' => fn($q) => $q->where('status', 'approved')->orderBy('date', 'asc'),
            'assessment',
        ]);

        $settings = SchoolSetting::where('school_id', $spkl->school_id)->pluck('value', 'key');

        $schoolLogoBase64 = null;
        if ($spkl->school->logo_path && Storage::disk('public')->exists($spkl->school->logo_path)) {
            $mime = Storage::disk('public')->mimeType($spkl->school->logo_path) ?? 'image/png';
            $content = base64_encode(Storage::disk('public')->get($spkl->school->logo_path));
            $schoolLogoBase64 = "data:{$mime};base64,{$content}";
        }

        $pdf = Pdf::loadView('reports.student_pdf', compact('spkl', 'settings', 'schoolLogoBase64'));
        $pdf->setPaper('a4', 'portrait');

        $sanitizedName = Str::slug($spkl->student->user->name);
        return $pdf->download("Laporan_PKL_{$sanitizedName}.pdf");
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
        $visits = $supervisionService->getVisitsForTeacher($teacher, $request->only(['company_id', 'stage']));

        $studentPkls = StudentPkl::where('teacher_id', $teacher->id)
            ->with(['company', 'pklPeriod'])
            ->get();

        $companies = $studentPkls->pluck('company')->filter()->unique('id')->values();
        $periods = $studentPkls->pluck('pklPeriod')->filter()->unique('id')->values();

        $stats = [
            'total' => SupervisionVisit::where('teacher_id', $teacher->id)->count(),
            'this_month' => SupervisionVisit::where('teacher_id', $teacher->id)
                ->whereYear('visit_date', now()->year)
                ->whereMonth('visit_date', now()->month)
                ->count(),
            'companies_count' => $companies->count(),
        ];

        return view('teacher.supervisions', compact('visits', 'companies', 'periods', 'stats'));
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
            'stage' => ['required', 'in:penyerahan,monitoring_1,monitoring_2,penjemputan,insidental'],
            'visit_date' => ['required', 'date'],
            'student_progress_summary' => ['required', 'string', 'min:10'],
            'company_feedback' => ['required', 'string', 'min:10'],
            'follow_up_agreement' => ['nullable', 'string'],
            'photo_with_students' => ['nullable', 'image', 'max:4096'],
            'photo_with_supervisor' => ['nullable', 'image', 'max:4096'],
        ]);

        $hasStudents = StudentPkl::where('teacher_id', $teacher->id)
            ->where('company_id', $validated['company_id'])
            ->where('pkl_period_id', $validated['pkl_period_id'])
            ->exists();

        if (!$hasStudents) {
            return back()->withErrors(['company_id' => 'Anda tidak memiliki alokasi siswa bimbingan aktif pada mitra industri dan periode ini.'])->withInput();
        }

        $supervisionService->createVisit(
            $teacher,
            $validated,
            $request->file('photo_with_students'),
            $request->file('photo_with_supervisor')
        );

        return back()->with('success', 'Log kunjungan supervisi berhasil dicatat dan Berita Acara siap diunduh.');
    }

    public function downloadBeritaAcaraPdf(SupervisionVisit $supervisionVisit, SupervisionService $supervisionService)
    {
        $teacher = $this->getTeacher();

        if ($supervisionVisit->teacher_id !== $teacher->id && Auth::user()->role !== 'admin-sekolah') {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mengunduh dokumen Berita Acara ini.');
        }

        try {
            $pdf = $supervisionService->generateBeritaAcaraPdf($supervisionVisit);
            $dateStr = $supervisionVisit->visit_date ? $supervisionVisit->visit_date->format('Ymd') : date('Ymd');
            $companySlug = Str::slug($supervisionVisit->company->name ?? 'DUDI');

            return $pdf->download("Berita_Acara_Supervisi_{$companySlug}_{$dateStr}.pdf");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Download Berita Acara PDF gagal (Visit ID: {$supervisionVisit->id}): " . $e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->with('error', 'Gagal memproses pengunduhan dokumen Berita Acara PDF: ' . $e->getMessage());
        }
    }
}
