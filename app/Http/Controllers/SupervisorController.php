<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Journal;
use App\Models\StudentPkl;
use App\Services\AssessmentService;
use App\Services\AttendanceService;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorController extends Controller
{
    protected function getSupervisor()
    {
        $supervisor = Auth::user()->supervisor;
        if (!$supervisor) {
            abort(403, 'Profil pembimbing DU/DI tidak ditemukan.');
        }
        return $supervisor;
    }

    public function dashboard()
    {
        $supervisor = $this->getSupervisor();
        $company = $supervisor->company;

        $studentPkls = StudentPkl::where('company_id', $company->id)
            ->with(['student.user', 'student.class'])
            ->get();
        $spklIds = $studentPkls->pluck('id');

        $today = Carbon::today()->toDateString();
        $todayAttendances = Attendance::whereIn('student_pkl_id', $spklIds)
            ->where('date', $today)
            ->with('studentPkl.student.user')
            ->get();

        $pendingRequests = AttendanceRequest::whereIn('student_pkl_id', $spklIds)
            ->whereIn('type', ['dinas_luar', 'luar_kantor'])
            ->where('status', 'pending')
            ->count();

        $pendingJournals = Journal::whereIn('student_pkl_id', $spklIds)
            ->where('status', 'submitted')
            ->count();

        return view('dudi.dashboard', compact(
            'company',
            'studentPkls',
            'todayAttendances',
            'pendingRequests',
            'pendingJournals'
        ));
    }

    public function attendances(Request $request)
    {
        $supervisor = $this->getSupervisor();
        $spklIds = StudentPkl::where('company_id', $supervisor->company_id)->pluck('id');

        $query = Attendance::whereIn('student_pkl_id', $spklIds)
            ->with(['studentPkl.student.user', 'studentPkl.student.class'])
            ->orderBy('date', 'desc');

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        $attendances = $query->paginate(20)->withQueryString();

        $baseStatsQuery = Attendance::whereIn('student_pkl_id', $spklIds);
        if ($request->filled('date')) {
            $baseStatsQuery->where('date', $request->date);
        }
        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'in_radius' => (clone $baseStatsQuery)->where('is_within_radius', true)->count(),
            'out_radius' => (clone $baseStatsQuery)->where('is_within_radius', false)->count(),
            'on_time' => (clone $baseStatsQuery)->where('status', 'hadir')->count(),
        ];

        return view('dudi.attendances', compact('attendances', 'stats'));
    }

    public function requests()
    {
        $supervisor = $this->getSupervisor();
        $spklIds = StudentPkl::where('company_id', $supervisor->company_id)->pluck('id');

        $requests = AttendanceRequest::whereIn('student_pkl_id', $spklIds)
            ->whereIn('type', ['dinas_luar', 'luar_kantor'])
            ->with(['studentPkl.student.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $baseStatsQuery = AttendanceRequest::whereIn('student_pkl_id', $spklIds)->whereIn('type', ['dinas_luar', 'luar_kantor']);
        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'pending' => (clone $baseStatsQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseStatsQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $baseStatsQuery)->where('status', 'rejected')->count(),
        ];

        return view('dudi.requests', compact('requests', 'stats'));
    }

    public function processRequest(Request $request, AttendanceRequest $attRequest, AttendanceService $attendanceService)
    {
        $supervisor = $this->getSupervisor();
        if ($attRequest->studentPkl->company_id !== $supervisor->company_id) {
            abort(403, 'Akses ditolak: Siswa ini tidak ditempatkan pada mitra industri Anda.');
        }

        $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'reviewer_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $attendanceService->processRequest($attRequest, $request->action, $request->reviewer_notes, Auth::user());

        $actionText = ($request->action === 'approve') ? 'disetujui' : 'ditolak';
        return back()->with('success', "Permohonan penugasan dinas/WFH berhasil {$actionText}.");
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
        $supervisor = $this->getSupervisor();
        $studentPkls = StudentPkl::where('company_id', $supervisor->company_id)
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

        $journals = $query->orderByRaw("CASE WHEN status = 'submitted' THEN 1 ELSE 2 END")
            ->orderBy('date', 'desc')
            ->paginate(15)
            ->withQueryString();

        $baseStatsQuery = Journal::whereIn('student_pkl_id', $spklIds);
        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'pending_dudi' => (clone $baseStatsQuery)->where('status', 'submitted')->count(),
            'approved_level1' => (clone $baseStatsQuery)->whereIn('status', ['review_guru', 'approved'])->count(),
            'revision' => (clone $baseStatsQuery)->where('status', 'revision')->count(),
        ];

        return view('dudi.journals', compact('journals', 'studentPkls', 'stats'));
    }

    public function processJournal(Request $request, Journal $journal, JournalService $journalService)
    {
        $supervisor = $this->getSupervisor();
        if ($journal->studentPkl->company_id !== $supervisor->company_id) {
            abort(403, 'Akses ditolak: Siswa ini tidak ditempatkan pada mitra industri Anda.');
        }

        $request->validate([
            'action' => ['required', 'in:approve,revision'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            if ($request->action === 'approve') {
                $journalService->approve($journal, Auth::user(), $request->notes);
                return back()->with('success', 'Jurnal berhasil disetujui tingkat 1 (Diteruskan ke Guru Pembimbing).');
            } else {
                if (empty($request->notes)) {
                    return back()->withErrors(['notes' => 'Catatan wajib diisi saat meminta revisi jurnal.']);
                }
                $journalService->requestRevision($journal, Auth::user(), $request->notes);
                return back()->with('success', 'Catatan revisi jurnal berhasil dikirimkan ke siswa.');
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
        $supervisor = $this->getSupervisor();
        $studentPkls = StudentPkl::where('company_id', $supervisor->company_id)
            ->with(['student.user', 'student.class.major', 'assessment'])
            ->get();

        $schoolId = $studentPkls->first()?->school_id;
        $weightDudi = (float) ($schoolId ? \App\Models\SchoolSetting::where('school_id', $schoolId)->where('key', 'weight_dudi')->value('value') ?? 60 : 60);
        $weightGuru = (float) ($schoolId ? \App\Models\SchoolSetting::where('school_id', $schoolId)->where('key', 'weight_guru')->value('value') ?? 40 : 40);

        return view('dudi.assessments', compact('studentPkls', 'weightDudi', 'weightGuru'));
    }

    public function storeAssessment(Request $request, AssessmentService $assessmentService)
    {
        $supervisor = $this->getSupervisor();

        $request->validate([
            'student_pkl_id' => ['required', 'exists:student_pkl,id'],
            'score_dudi_discipline' => ['required', 'numeric', 'min:0', 'max:100'],
            'score_dudi_technical' => ['required', 'numeric', 'min:0', 'max:100'],
            'score_dudi_initiative' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes_dudi' => ['nullable', 'string'],
        ]);

        $spkl = StudentPkl::findOrFail($request->student_pkl_id);

        if ($spkl->company_id !== $supervisor->company_id) {
            abort(403, 'Akses ditolak: Siswa ini tidak ditempatkan pada mitra industri Anda.');
        }

        // Thread-safe and atomic assessment scoring
        $assessmentService->saveSupervisorAssessment($spkl, [
            'score_dudi_discipline' => $request->score_dudi_discipline,
            'score_dudi_technical' => $request->score_dudi_technical,
            'score_dudi_initiative' => $request->score_dudi_initiative,
            'notes_dudi' => $request->notes_dudi,
        ]);

        return back()->with('success', 'Nilai kompetensi industri berhasil disimpan.');
    }
}
