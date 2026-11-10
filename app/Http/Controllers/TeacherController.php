<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Journal;
use App\Models\SchoolSetting;
use App\Models\StudentPkl;
use App\Services\AssessmentService;
use App\Services\JournalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

    public function dashboard()
    {
        $teacher = $this->getTeacher();
        $studentPkls = StudentPkl::where('teacher_id', $teacher->id)->with(['student.user', 'company'])->get();
        $totalStudents = $studentPkls->count();

        $today = Carbon::today()->toDateString();
        $spklIds = $studentPkls->pluck('id');

        $todayAttendances = Attendance::whereIn('student_pkl_id', $spklIds)
            ->where('date', $today)
            ->get();

        $pendingRequests = AttendanceRequest::whereIn('student_pkl_id', $spklIds)
            ->whereIn('type', ['izin', 'sakit'])
            ->where('status', 'pending')
            ->count();

        $pendingJournals = Journal::whereIn('student_pkl_id', $spklIds)
            ->where('status', 'review_guru')
            ->count();

        return view('teacher.dashboard', compact(
            'totalStudents',
            'todayAttendances',
            'pendingRequests',
            'pendingJournals',
            'studentPkls'
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

        return view('teacher.monitoring', compact('attendances'));
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

        return view('teacher.requests', compact('requests'));
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
            'reviewer_role' => 'guru-pembimbing',
            'reviewer_notes' => $request->reviewer_notes,
            'reviewed_at' => now(),
        ]);

        // If approved, create or update attendance record for the date range
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
                        'checkout_notes' => 'Pengajuan disetujui Guru: ' . $attRequest->reason,
                    ]
                );
                $currentDate->addDay();
            }
        }

        $actionText = ($status === 'approved') ? 'disetujui' : 'ditolak';
        return back()->with('success', "Permohonan presensi siswa berhasil {$actionText}.");
    }

    public function journals()
    {
        $teacher = $this->getTeacher();
        $spklIds = StudentPkl::where('teacher_id', $teacher->id)->pluck('id');

        $journals = Journal::whereIn('student_pkl_id', $spklIds)
            ->with(['studentPkl.student.user', 'studentPkl.company', 'photos', 'approvals.approver'])
            ->orderByRaw("CASE WHEN status = 'review_guru' THEN 1 ELSE 2 END")
            ->orderBy('date', 'desc')
            ->paginate(15);

        return view('teacher.journals', compact('journals'));
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

    public function assessments()
    {
        $teacher = $this->getTeacher();
        $studentPkls = StudentPkl::where('teacher_id', $teacher->id)
            ->with(['student.user', 'student.class.major', 'company', 'assessment'])
            ->get();

        return view('teacher.assessments', compact('studentPkls'));
    }

    public function storeAssessment(Request $request, AssessmentService $assessmentService)
    {
        $request->validate([
            'student_pkl_id' => ['required', 'exists:student_pkl,id'],
            'score_teacher_report' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes_teacher' => ['nullable', 'string'],
        ]);

        $spkl = StudentPkl::findOrFail($request->student_pkl_id);

        $assessment = Assessment::updateOrCreate(
            ['student_pkl_id' => $spkl->id],
            [
                'school_id' => $spkl->school_id,
                'score_teacher_report' => $request->score_teacher_report,
                'notes_teacher' => $request->notes_teacher,
            ]
        );

        $assessment->final_score = $assessmentService->calculateFinalScore($assessment);
        $assessment->save();

        return back()->with('success', 'Nilai pembimbingan dan laporan sekolah berhasil disimpan. Nilai akhir telah dikalkulasi.');
    }

    public function reports()
    {
        $teacher = $this->getTeacher();
        $studentPkls = StudentPkl::where('teacher_id', $teacher->id)
            ->with(['student.user', 'student.class.major', 'company', 'assessment'])
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

        $pdf = Pdf::loadView('reports.student_pdf', compact('spkl', 'settings'));
        $pdf->setPaper('a4', 'portrait');

        $sanitizedName = Str::slug($spkl->student->user->name);
        return $pdf->download("Laporan_PKL_{$sanitizedName}.pdf");
    }
}
