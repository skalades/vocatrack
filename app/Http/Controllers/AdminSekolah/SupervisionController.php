<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Models\Company;
use App\Models\PklPeriod;
use App\Models\SupervisionAssignment;
use App\Models\SupervisionAssignmentStudent;
use App\Models\SupervisionVisit;
use App\Models\Teacher;
use App\Services\SupervisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SupervisionController extends BaseAdminSekolahController
{
    public function index(Request $request, SupervisionService $supervisionService)
    {
        $schoolId = $this->getSchoolId();

        $periods = PklPeriod::where('school_id', $schoolId)->latest()->get();
        $activePeriod = $periods->firstWhere('is_active', true) ?? $periods->first();
        $selectedPeriodId = (int) ($request->query('pkl_period_id') ?: ($activePeriod?->id ?? 0));

        $teachers = Teacher::whereHas('user', fn ($q) => $q->where('school_id', $schoolId)->where('is_active', true))
            ->with(['user', 'headedMajors', 'majors'])
            ->get()
            ->sortBy(fn (Teacher $t) => $t->user?->name ?? '')
            ->values();

        $companies = Company::where('school_id', $schoolId)->orderBy('name')->get();

        $assignments = $supervisionService->getAssignments(
            $schoolId,
            $request->only(['pkl_period_id', 'stage', 'teacher_id', 'company_id'])
        );

        $visits = $supervisionService->getVisitsForSchool(
            $schoolId,
            $request->only(['teacher_id', 'company_id', 'stage'])
        );

        $matrix = $selectedPeriodId > 0
            ? $supervisionService->getCompanyMonitoringMatrix($schoolId, $selectedPeriodId)
            : collect();

        $stats = [
            'total_assignments' => SupervisionAssignment::where('school_id', $schoolId)->count(),
            'pending_targets' => SupervisionAssignmentStudent::whereHas('assignment', fn ($q) => $q->where('school_id', $schoolId))
                ->whereNull('completed_at')
                ->count(),
            'completed_targets' => SupervisionAssignmentStudent::whereHas('assignment', fn ($q) => $q->where('school_id', $schoolId))
                ->whereNotNull('completed_at')
                ->count(),
            'total_visits' => SupervisionVisit::where('school_id', $schoolId)->count(),
        ];

        return view('adminsekolah.supervisions', compact(
            'periods',
            'selectedPeriodId',
            'teachers',
            'companies',
            'assignments',
            'visits',
            'matrix',
            'stats'
        ));
    }

    public function storeAssignment(Request $request, SupervisionService $supervisionService)
    {
        $schoolId = $this->getSchoolId();

        $validated = $request->validate([
            'teacher_id' => ['required', 'integer'],
            'pkl_period_id' => ['required', Rule::exists('pkl_periods', 'id')->where('school_id', $schoolId)],
            'stage' => ['required', 'in:monitoring_1,monitoring_2'],
            'assignment_number' => ['nullable', 'string', 'max:100'],
            'scheduled_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['integer'],
            'student_pkl_ids' => ['nullable', 'array'],
            'student_pkl_ids.*' => ['integer'],
        ], [
            'teacher_id.required' => 'Pilih guru atau Kajur petugas monitoring terlebih dahulu.',
            'pkl_period_id.required' => 'Pilih periode PKL terlebih dahulu.',
            'stage.required' => 'Pilih tahap monitoring (Monitoring 1 atau Monitoring 2).',
        ]);

        if (empty($validated['student_pkl_ids']) && empty($validated['company_ids'])) {
            return back()->withErrors(['company_ids' => 'Pilih minimal satu DU/DI atau siswa target monitoring.'])->withInput();
        }

        $assignment = $supervisionService->createAssignment(Auth::user(), $schoolId, $validated);
        $companiesCount = $assignment->items->pluck('company_id')->unique()->count();
        $studentsCount = $assignment->items->count();

        return back()->with('success', "Penugasan {$assignment->stage_short_label} untuk {$assignment->teacher->user->name} berhasil dibuat ({$companiesCount} DU/DI, {$studentsCount} siswa).");
    }

    public function destroyAssignment(SupervisionAssignment $assignment, SupervisionService $supervisionService)
    {
        abort_if((int) $assignment->school_id !== $this->getSchoolId(), 403);

        $result = $supervisionService->cancelPendingAssignment($assignment);
        $message = $result['deleted_assignment']
            ? 'Penugasan monitoring berhasil dibatalkan.'
            : "Sebanyak {$result['removed_items']} target siswa yang belum selesai berhasil dilepaskan dari penugasan.";

        return back()->with('success', $message);
    }

    public function downloadBeritaAcaraPdf(SupervisionVisit $supervisionVisit, SupervisionService $supervisionService)
    {
        abort_if(!$supervisionService->canViewVisit(Auth::user(), $supervisionVisit), 403, 'Akses ditolak.');

        try {
            $pdf = $supervisionService->generateBeritaAcaraPdf($supervisionVisit);

            return $pdf->download($supervisionService->buildBeritaAcaraFilename($supervisionVisit));
        } catch (\Throwable $e) {
            Log::error("Download Berita Acara PDF gagal (Visit ID: {$supervisionVisit->id}): " . $e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Gagal mengunduh Berita Acara PDF: ' . $e->getMessage());
        }
    }
}
