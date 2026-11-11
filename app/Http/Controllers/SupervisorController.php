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
        $supervisor = $this->getSupervisor();
        if ($attRequest->studentPkl->company_id !== $supervisor->company_id) {
            abort(403, 'Akses ditolak: Siswa ini tidak ditempatkan pada mitra industri Anda.');
        }

        $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'reviewer_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $status = ($request->action === 'approve') ? 'approved' : 'rejected';

        \Illuminate\Support\Facades\DB::transaction(function () use ($attRequest, $request, $status) {
            // Pessimistic lock row to prevent race conditions or double review
            $lockedRequest = AttendanceRequest::where('id', $attRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== 'pending') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => 'Permohonan presensi ini sudah diproses sebelumnya (Status: ' . $lockedRequest->status . ').'
                ]);
            }

            $lockedRequest->update([
                'status' => $status,
                'reviewer_id' => Auth::id(),
                'reviewer_role' => 'pembimbing-dudi',
                'reviewer_notes' => $request->reviewer_notes,
                'reviewed_at' => now(),
            ]);

            if ($status === 'approved') {
                $currentDate = Carbon::parse($lockedRequest->start_date);
                $endDate = Carbon::parse($lockedRequest->end_date);

                while ($currentDate->lessThanOrEqualTo($endDate)) {
                    Attendance::updateOrCreate(
                        [
                            'student_pkl_id' => $lockedRequest->student_pkl_id,
                            'date' => $currentDate->toDateString(),
                        ],
                        [
                            'school_id' => $lockedRequest->school_id,
                            'status' => $lockedRequest->type,
                            'is_within_radius' => true,
                            'checkout_notes' => 'Disetujui Mentor DU/DI: ' . $lockedRequest->reason,
                        ]
                    );
                    $currentDate->addDay();
                }
            }
        });

        $actionText = ($status === 'approved') ? 'disetujui' : 'ditolak';
        return back()->with('success', "Permohonan penugasan dinas/WFH berhasil {$actionText}.");
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

        return view('dudi.journals', compact('journals', 'studentPkls'));
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
