<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Journal;
use App\Models\StudentPkl;
use App\Services\AssessmentService;
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

        return view('dudi.attendances', compact('attendances'));
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

        return view('dudi.requests', compact('requests'));
    }

    public function processRequest(Request $request, AttendanceRequest $attRequest)
    {
        $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'reviewer_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $status = ($request->action === 'approve') ? 'approved' : 'rejected';

        $attRequest->update([
            'status' => $status,
            'reviewer_id' => Auth::id(),
            'reviewer_role' => 'pembimbing-dudi',
            'reviewer_notes' => $request->reviewer_notes,
            'reviewed_at' => now(),
        ]);

        if ($status === 'approved') {
            $currentDate = Carbon::parse($attRequest->start_date);
            $endDate = Carbon::parse($attRequest->end_date);

            while ($currentDate->lessThanOrEqualTo($endDate)) {
                Attendance::updateOrCreate(
                    [
                        'student_pkl_id' => $attRequest->student_pkl_id,
                        'date' => $currentDate->toDateString(),
                    ],
                    [
                        'school_id' => $attRequest->school_id,
                        'status' => $attRequest->type,
                        'is_within_radius' => true,
                        'checkout_notes' => 'Disetujui Mentor DU/DI: ' . $attRequest->reason,
                    ]
                );
                $currentDate->addDay();
            }
        }

        $actionText = ($status === 'approved') ? 'disetujui' : 'ditolak';
        return back()->with('success', "Permohonan penugasan dinas/WFH berhasil {$actionText}.");
    }

    public function journals()
    {
        $supervisor = $this->getSupervisor();
        $spklIds = StudentPkl::where('company_id', $supervisor->company_id)->pluck('id');

        $journals = Journal::whereIn('student_pkl_id', $spklIds)
            ->with(['studentPkl.student.user', 'photos', 'approvals.approver'])
            ->orderByRaw("CASE WHEN status = 'submitted' THEN 1 ELSE 2 END")
            ->orderBy('date', 'desc')
            ->paginate(15);

        return view('dudi.journals', compact('journals'));
    }

    public function processJournal(Request $request, Journal $journal, JournalService $journalService)
    {
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

    public function assessments()
    {
        $supervisor = $this->getSupervisor();
        $studentPkls = StudentPkl::where('company_id', $supervisor->company_id)
            ->with(['student.user', 'student.class.major', 'assessment'])
            ->get();

        return view('dudi.assessments', compact('studentPkls'));
    }

    public function storeAssessment(Request $request, AssessmentService $assessmentService)
    {
        $request->validate([
            'student_pkl_id' => ['required', 'exists:student_pkl,id'],
            'score_dudi_discipline' => ['required', 'numeric', 'min:0', 'max:100'],
            'score_dudi_technical' => ['required', 'numeric', 'min:0', 'max:100'],
            'score_dudi_initiative' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes_dudi' => ['nullable', 'string'],
        ]);

        $spkl = StudentPkl::findOrFail($request->student_pkl_id);

        $assessment = Assessment::updateOrCreate(
            ['student_pkl_id' => $spkl->id],
            [
                'school_id' => $spkl->school_id,
                'score_dudi_discipline' => $request->score_dudi_discipline,
                'score_dudi_technical' => $request->score_dudi_technical,
                'score_dudi_initiative' => $request->score_dudi_initiative,
                'notes_dudi' => $request->notes_dudi,
            ]
        );

        $assessment->final_score = $assessmentService->calculateFinalScore($assessment);
        $assessment->save();

        return back()->with('success', 'Nilai kompetensi industri berhasil disimpan.');
    }
}
