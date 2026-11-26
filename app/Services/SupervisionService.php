<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\Journal;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\StudentPkl;
use App\Models\SupervisionAssignment;
use App\Models\SupervisionAssignmentStudent;
use App\Models\SupervisionVisit;
use App\Models\Teacher;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfInstance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupervisionService
{
    public const PRIMARY_STAGES = ['monitoring_1', 'monitoring_2'];

    /**
     * Buat penugasan monitoring baru (mendukung 1 guru/kajur ke banyak DU/DI sekaligus).
     *
     * @throws ValidationException
     */
    public function createAssignment(User $actor, int $schoolId, array $data, ?Major $scopedMajor = null): SupervisionAssignment
    {
        return DB::transaction(function () use ($actor, $schoolId, $data, $scopedMajor) {
            $teacher = Teacher::where('id', $data['teacher_id'])
                ->whereHas('user', fn (Builder $q) => $q->where('school_id', $schoolId))
                ->first();

            if (!$teacher) {
                throw ValidationException::withMessages([
                    'teacher_id' => 'Guru atau Kajur petugas monitoring tidak ditemukan pada sekolah ini.',
                ]);
            }

            $period = PklPeriod::where('id', $data['pkl_period_id'])
                ->where('school_id', $schoolId)
                ->first();

            if (!$period) {
                throw ValidationException::withMessages([
                    'pkl_period_id' => 'Periode PKL tidak valid untuk sekolah ini.',
                ]);
            }

            $stage = $data['stage'];
            if (!in_array($stage, self::PRIMARY_STAGES, true)) {
                throw ValidationException::withMessages([
                    'stage' => 'Tahap penugasan hanya mendukung Monitoring 1 atau Monitoring 2.',
                ]);
            }

            $studentPkls = $this->resolveTargetStudentPklsForAssignment($schoolId, $period->id, $data, $scopedMajor);

            if ($studentPkls->isEmpty()) {
                throw ValidationException::withMessages([
                    'company_ids' => 'Tidak ada siswa PKL aktif yang memenuhi kriteria pada DU/DI dan periode yang dipilih.',
                ]);
            }

            $studentPklIds = $studentPkls->pluck('id')->all();

            // Tolak jika ada siswa yang sudah menyelesaikan tahap monitoring ini
            $alreadyCompleted = SupervisionAssignmentStudent::whereIn('student_pkl_id', $studentPklIds)
                ->where('stage', $stage)
                ->whereNotNull('completed_at')
                ->with('studentPkl.student.user')
                ->get();

            if ($alreadyCompleted->isNotEmpty()) {
                $names = $alreadyCompleted->map(fn ($item) => $item->studentPkl?->student?->user?->name)->filter()->take(3)->implode(', ');
                throw ValidationException::withMessages([
                    'student_pkl_ids' => "Sebagian siswa sudah menyelesaikan {$stage}: {$names}.",
                ]);
            }

            $visitedStudentIds = $this->getVisitedStudentPklIdsForStage($studentPklIds, $stage);
            if ($visitedStudentIds->isNotEmpty()) {
                $names = $studentPkls->whereIn('id', $visitedStudentIds)->map(fn ($spkl) => $spkl->student?->user?->name)->filter()->take(3)->implode(', ');
                throw ValidationException::withMessages([
                    'student_pkl_ids' => "Sebagian siswa sudah memiliki laporan kunjungan {$stage}: {$names}.",
                ]);
            }

            // Reassign: bersihkan penugasan pending sebelumnya untuk (student_pkl_id, stage) yang sama
            $existingPending = SupervisionAssignmentStudent::whereIn('student_pkl_id', $studentPklIds)
                ->where('stage', $stage)
                ->whereNull('completed_at')
                ->get();

            $affectedOldAssignmentIds = $existingPending->pluck('supervision_assignment_id')->unique()->values();
            if ($existingPending->isNotEmpty()) {
                SupervisionAssignmentStudent::whereIn('id', $existingPending->pluck('id'))->delete();
                $this->pruneEmptyAssignments($affectedOldAssignmentIds);
            }

            $assignment = SupervisionAssignment::create([
                'school_id' => $schoolId,
                'pkl_period_id' => $period->id,
                'teacher_id' => $teacher->id,
                'assigned_by' => $actor->id,
                'major_id' => $scopedMajor?->id,
                'stage' => $stage,
                'assignment_number' => $data['assignment_number'] ?? null,
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $now = now();
            $rows = $studentPkls->map(fn (StudentPkl $spkl) => [
                'supervision_assignment_id' => $assignment->id,
                'student_pkl_id' => $spkl->id,
                'company_id' => $spkl->company_id,
                'pkl_period_id' => $period->id,
                'stage' => $stage,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            SupervisionAssignmentStudent::insert($rows);

            return $assignment->load(['teacher.user', 'pklPeriod', 'items.company', 'items.studentPkl.student.user']);
        });
    }

    /**
     * Hapus atau batalkan item penugasan yang belum selesai.
     *
     * @return array{deleted_assignment: bool, removed_items: int}
     */
    public function cancelPendingAssignment(SupervisionAssignment $assignment, ?Major $scopedMajor = null): array
    {
        if ($scopedMajor && (int) $assignment->major_id !== (int) $scopedMajor->id) {
            $hasExternalStudent = $assignment->items()
                ->whereHas('studentPkl.student.class', fn (Builder $q) => $q->where('major_id', '!=', $scopedMajor->id))
                ->exists();

            if ($hasExternalStudent) {
                throw ValidationException::withMessages([
                    'assignment' => 'Anda hanya dapat membatalkan penugasan yang mencakup siswa jurusan Anda.',
                ]);
            }
        }

        return DB::transaction(function () use ($assignment) {
            $pendingCount = $assignment->items()->whereNull('completed_at')->count();
            $completedCount = $assignment->items()->whereNotNull('completed_at')->count();

            if ($pendingCount === 0 && $completedCount > 0) {
                throw ValidationException::withMessages([
                    'assignment' => 'Penugasan ini sudah selesai seluruhnya dan tidak dapat dibatalkan.',
                ]);
            }

            $assignment->items()->whereNull('completed_at')->delete();

            $deletedWhole = false;
            if ($completedCount === 0 && !$assignment->visits()->exists()) {
                $assignment->delete();
                $deletedWhole = true;
            }

            return [
                'deleted_assignment' => $deletedWhole,
                'removed_items' => $pendingCount,
            ];
        });
    }

    /**
     * Catat kunjungan supervisi baru (mendukung guru pembimbing asli maupun petugas monitoring yang ditugaskan).
     *
     * @throws ValidationException
     */
    public function createVisit(Teacher $teacher, array $data, ?UploadedFile $photoStudents = null, ?UploadedFile $photoSupervisor = null): SupervisionVisit
    {
        return DB::transaction(function () use ($teacher, $data, $photoStudents, $photoSupervisor) {
            $companyId = (int) $data['company_id'];
            $periodId = (int) $data['pkl_period_id'];
            $stage = $data['stage'];
            $assignmentId = isset($data['supervision_assignment_id']) ? (int) $data['supervision_assignment_id'] : null;
            $requestedStudentPklIds = !empty($data['student_pkl_ids']) && is_array($data['student_pkl_ids'])
                ? array_map('intval', $data['student_pkl_ids'])
                : null;

            $eligible = $this->resolveEligibleStudentsForVisit(
                $teacher,
                $companyId,
                $periodId,
                $stage,
                $assignmentId,
                $requestedStudentPklIds
            );

            if ($eligible['students']->isEmpty()) {
                throw ValidationException::withMessages([
                    'company_id' => $eligible['error'] ?? 'Anda tidak memiliki alokasi siswa bimbingan atau penugasan monitoring aktif pada mitra industri dan periode ini.',
                ]);
            }

            $photoStudentsPath = $photoStudents ? $photoStudents->store('supervisions/students', 'public') : null;
            $photoSupervisorPath = $photoSupervisor ? $photoSupervisor->store('supervisions/supervisors', 'public') : null;

            $visit = SupervisionVisit::create([
                'school_id' => $teacher->user->school_id,
                'teacher_id' => $teacher->id,
                'company_id' => $companyId,
                'pkl_period_id' => $periodId,
                'supervision_assignment_id' => $eligible['assignment_id'],
                'stage' => $stage,
                'visit_date' => $data['visit_date'],
                'student_progress_summary' => $data['student_progress_summary'],
                'company_feedback' => $data['company_feedback'],
                'follow_up_agreement' => $data['follow_up_agreement'] ?? null,
                'photo_with_students_path' => $photoStudentsPath,
                'photo_with_supervisor_path' => $photoSupervisorPath,
            ]);

            $coveredIds = $eligible['students']->pluck('id')->all();
            $visit->studentPkls()->syncWithoutDetaching($coveredIds);

            // Jika monitoring_1 / monitoring_2, tandai penugasan siswa terkait selesai (menutup akses read-only petugas)
            if (in_array($stage, self::PRIMARY_STAGES, true)) {
                $pendingAssignmentItemIds = SupervisionAssignmentStudent::whereIn('student_pkl_id', $coveredIds)
                    ->where('stage', $stage)
                    ->whereNull('completed_at')
                    ->whereHas('assignment', fn (Builder $q) => $q->where('teacher_id', $teacher->id))
                    ->pluck('id');

                if ($pendingAssignmentItemIds->isNotEmpty()) {
                    SupervisionAssignmentStudent::whereIn('id', $pendingAssignmentItemIds)->update([
                        'supervision_visit_id' => $visit->id,
                        'completed_at' => now(),
                    ]);
                }
            }

            return $visit;
        });
    }

    /**
     * Tentukan daftar siswa yang berhak disupervisi oleh guru pada DU/DI, periode, dan tahapan tertentu.
     *
     * @return array{students: Collection<int, StudentPkl>, assignment_id: ?int, error: ?string}
     */
    public function resolveEligibleStudentsForVisit(
        Teacher $teacher,
        int $companyId,
        int $periodId,
        string $stage,
        ?int $assignmentId = null,
        ?array $requestedStudentPklIds = null
    ): array {
        $schoolId = (int) $teacher->user->school_id;

        if (in_array($stage, self::PRIMARY_STAGES, true)) {
            $assignedQuery = SupervisionAssignmentStudent::where('company_id', $companyId)
                ->where('pkl_period_id', $periodId)
                ->where('stage', $stage)
                ->whereNull('completed_at')
                ->whereHas('assignment', function (Builder $q) use ($teacher, $schoolId, $assignmentId) {
                    $q->where('school_id', $schoolId)
                        ->where('teacher_id', $teacher->id);
                    if ($assignmentId) {
                        $q->where('id', $assignmentId);
                    }
                })
                ->with('studentPkl.student.user');

            $assignedItems = $assignedQuery->get();
            $matchedAssignmentId = $assignedItems->first()?->supervision_assignment_id;
            $assignedStudents = $assignedItems->pluck('studentPkl')->filter();

            // Jika assignmentId spesifik diminta namun tidak ditemukan/sudah selesai
            if ($assignmentId && $assignedStudents->isEmpty()) {
                return [
                    'students' => collect(),
                    'assignment_id' => null,
                    'error' => 'Penugasan monitoring untuk perusahaan ini tidak ditemukan atau laporannya sudah selesai.',
                ];
            }

            // Fallback bimbingan sendiri: siswa bimbingan guru ini yang TIDAK dialihkan ke guru lain pada tahap ini
            $ownStudents = collect();
            if (!$assignmentId) {
                $candidateOwn = StudentPkl::where('school_id', $schoolId)
                    ->where('teacher_id', $teacher->id)
                    ->where('company_id', $companyId)
                    ->where('pkl_period_id', $periodId)
                    ->whereIn('status', ['assigned', 'active'])
                    ->with('student.user')
                    ->get();

                if ($candidateOwn->isNotEmpty()) {
                    $candidateIds = $candidateOwn->pluck('id')->all();
                    $delegatedOrCompletedIds = SupervisionAssignmentStudent::whereIn('student_pkl_id', $candidateIds)
                        ->where('stage', $stage)
                        ->pluck('student_pkl_id')
                        ->all();
                    $alreadyVisitedIds = $this->getVisitedStudentPklIdsForStage($candidateIds, $stage)->all();

                    $blockedIds = array_unique(array_merge($delegatedOrCompletedIds, $alreadyVisitedIds));
                    $ownStudents = $candidateOwn->reject(fn ($spkl) => in_array($spkl->id, $blockedIds, true));
                }
            }

            $merged = $assignedStudents->concat($ownStudents)->unique('id')->values();

            if ($requestedStudentPklIds !== null) {
                $merged = $merged->whereIn('id', $requestedStudentPklIds)->values();
            }

            if ($merged->isEmpty()) {
                $delegatedToOther = SupervisionAssignmentStudent::where('company_id', $companyId)
                    ->where('pkl_period_id', $periodId)
                    ->where('stage', $stage)
                    ->whereHas('studentPkl', fn (Builder $q) => $q->where('teacher_id', $teacher->id))
                    ->whereHas('assignment', fn (Builder $q) => $q->where('teacher_id', '!=', $teacher->id))
                    ->exists();

                $error = $delegatedToOther
                    ? "Tahap {$stage} untuk siswa bimbingan Anda pada DU/DI ini sedang/telah ditugaskan ke petugas monitoring lain."
                    : null;

                return [
                    'students' => collect(),
                    'assignment_id' => $matchedAssignmentId,
                    'error' => $error,
                ];
            }

            return [
                'students' => $merged,
                'assignment_id' => $matchedAssignmentId,
                'error' => null,
            ];
        }

        // Tahap insidental atau historis: boleh untuk siswa bimbingan sendiri ATAU siswa yang sedang ditugaskan (pending) ke guru ini
        $ownStudents = StudentPkl::where('school_id', $schoolId)
            ->where('teacher_id', $teacher->id)
            ->where('company_id', $companyId)
            ->where('pkl_period_id', $periodId)
            ->whereIn('status', ['assigned', 'active'])
            ->with('student.user')
            ->get();

        $activeAssignedStudents = SupervisionAssignmentStudent::where('company_id', $companyId)
            ->where('pkl_period_id', $periodId)
            ->whereNull('completed_at')
            ->whereHas('assignment', fn (Builder $q) => $q->where('school_id', $schoolId)->where('teacher_id', $teacher->id))
            ->with('studentPkl.student.user')
            ->get()
            ->pluck('studentPkl')
            ->filter();

        $merged = $ownStudents->concat($activeAssignedStudents)->unique('id')->values();

        if ($requestedStudentPklIds !== null) {
            $merged = $merged->whereIn('id', $requestedStudentPklIds)->values();
        }

        return [
            'students' => $merged,
            'assignment_id' => null,
            'error' => null,
        ];
    }

    /**
     * Daftar tugas monitoring milik guru/kajur (dikelompokkan per penugasan + perusahaan).
     */
    public function getAssignedTargetsForTeacher(Teacher $teacher): Collection
    {
        $items = SupervisionAssignmentStudent::whereHas('assignment', function (Builder $q) use ($teacher) {
            $q->where('school_id', $teacher->user->school_id)
                ->where('teacher_id', $teacher->id);
        })
            ->with([
                'assignment.assignedBy',
                'company',
                'pklPeriod',
                'studentPkl.student.user',
                'studentPkl.student.class.major',
                'studentPkl.teacher.user',
                'visit',
            ])
            ->get();

        return $items->groupBy(fn (SupervisionAssignmentStudent $item) => $item->supervision_assignment_id . ':' . $item->company_id)
            ->map(function (Collection $group) {
                /** @var SupervisionAssignmentStudent $first */
                $first = $group->first();
                $pendingCount = $group->whereNull('completed_at')->count();

                return [
                    'assignment' => $first->assignment,
                    'company' => $first->company,
                    'pkl_period' => $first->pklPeriod,
                    'stage' => $first->stage,
                    'stage_label' => $first->assignment->stage_label,
                    'stage_short_label' => $first->assignment->stage_short_label,
                    'stage_badge_class' => $first->assignment->stage_badge_class,
                    'students' => $group->pluck('studentPkl')->filter()->values(),
                    'pending_count' => $pendingCount,
                    'total_count' => $group->count(),
                    'is_completed' => $pendingCount === 0,
                    'completed_at' => $group->whereNotNull('completed_at')->max('completed_at'),
                    'visit' => $group->firstWhere('supervision_visit_id', '!=', null)?->visit,
                ];
            })
            ->sortBy([
                ['is_completed', 'asc'],
                fn ($row) => $row['assignment']->scheduled_date?->timestamp ?? PHP_INT_MAX,
            ])
            ->values();
    }

    /**
     * Data briefing read-only untuk guru/kajur petugas sebelum mengisi laporan kunjungan di perusahaan tertentu.
     * Otomatis null jika penugasan untuk perusahaan tersebut sudah selesai (menutup akses read-only).
     */
    public function getReadOnlyBriefingForAssignmentCompany(Teacher $teacher, SupervisionAssignment $assignment, Company $company): ?array
    {
        if ((int) $assignment->teacher_id !== (int) $teacher->id || (int) $assignment->school_id !== (int) $teacher->user->school_id) {
            return null;
        }

        $pendingItems = SupervisionAssignmentStudent::where('supervision_assignment_id', $assignment->id)
            ->where('company_id', $company->id)
            ->whereNull('completed_at')
            ->with([
                'studentPkl.student.user',
                'studentPkl.student.class.major',
                'studentPkl.teacher.user',
                'studentPkl.supervisor.user',
            ])
            ->get();

        if ($pendingItems->isEmpty()) {
            return null;
        }

        $studentPkls = $pendingItems->pluck('studentPkl')->filter()->values();
        $spklIds = $studentPkls->pluck('id')->all();

        $attendanceSummary = Attendance::whereIn('student_pkl_id', $spklIds)
            ->selectRaw("student_pkl_id,
                SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir_count,
                SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat_count,
                SUM(CASE WHEN status IN ('izin', 'sakit', 'dinas_luar', 'luar_kantor') THEN 1 ELSE 0 END) as izin_count,
                SUM(CASE WHEN status = 'alfa' THEN 1 ELSE 0 END) as alfa_count")
            ->groupBy('student_pkl_id')
            ->get()
            ->keyBy('student_pkl_id');

        $recentAttendances = Attendance::whereIn('student_pkl_id', $spklIds)
            ->orderByDesc('date')
            ->get()
            ->groupBy('student_pkl_id')
            ->map(fn (Collection $rows) => $rows->take(7)->values());

        $recentJournals = Journal::whereIn('student_pkl_id', $spklIds)
            ->orderByDesc('date')
            ->get()
            ->groupBy('student_pkl_id')
            ->map(fn (Collection $rows) => $rows->take(5)->values());

        $incidents = IncidentReport::whereIn('student_pkl_id', $spklIds)
            ->latest()
            ->get()
            ->groupBy('student_pkl_id');

        return [
            'assignment' => $assignment->loadMissing(['pklPeriod', 'assignedBy']),
            'company' => $company,
            'student_pkls' => $studentPkls,
            'attendance_summary' => $attendanceSummary,
            'recent_attendances' => $recentAttendances,
            'recent_journals' => $recentJournals,
            'incidents' => $incidents,
        ];
    }

    /**
     * Ambil opsi DU/DI & periode yang dapat dipilih guru pada modal Catat Kunjungan (bimbingan sendiri + tugas aktif).
     */
    public function getVisitFormOptionsForTeacher(Teacher $teacher): array
    {
        $schoolId = (int) $teacher->user->school_id;

        $ownPkls = StudentPkl::where('school_id', $schoolId)
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', ['assigned', 'active'])
            ->with(['company', 'pklPeriod'])
            ->get();

        $pendingAssignedItems = SupervisionAssignmentStudent::whereNull('completed_at')
            ->whereHas('assignment', fn (Builder $q) => $q->where('school_id', $schoolId)->where('teacher_id', $teacher->id))
            ->with(['company', 'pklPeriod'])
            ->get();

        $companies = $ownPkls->pluck('company')
            ->concat($pendingAssignedItems->pluck('company'))
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $periods = $ownPkls->pluck('pklPeriod')
            ->concat($pendingAssignedItems->pluck('pklPeriod'))
            ->filter()
            ->unique('id')
            ->values();

        return compact('companies', 'periods');
    }

    /**
     * Ambil riwayat supervisi untuk guru (baik sebagai petugas pelaksana maupun sebagai pembimbing asli siswa).
     */
    public function getVisitsForTeacher(Teacher $teacher, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $roleScope = $filters['role_scope'] ?? null;

        $query = SupervisionVisit::where('school_id', $teacher->user->school_id)
            ->with(['teacher.user', 'company', 'pklPeriod', 'assignment', 'studentPkls.student.user', 'studentPkls.teacher.user'])
            ->latest('visit_date')
            ->latest('id');

        if ($roleScope === 'visitor') {
            $query->where('teacher_id', $teacher->id);
        } elseif ($roleScope === 'advisor') {
            $query->where(function (Builder $q) use ($teacher) {
                $q->whereHas('studentPkls', fn (Builder $sq) => $sq->where('student_pkl.teacher_id', $teacher->id))
                    ->orWhere(function (Builder $legacyQ) use ($teacher) {
                        $legacyQ->doesntHave('studentPkls')->where('teacher_id', $teacher->id);
                    });
            });
        } else {
            $query->where(function (Builder $q) use ($teacher) {
                $q->where('teacher_id', $teacher->id)
                    ->orWhereHas('studentPkls', fn (Builder $sq) => $sq->where('student_pkl.teacher_id', $teacher->id));
            });
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Ambil riwayat supervisi yang relevan bagi jurusan Kajur (berdasarkan siswa jurusan terkait).
     */
    public function getVisitsForMajor(int $schoolId, Major $major, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $classIds = $major->classes()->pluck('id');

        $query = SupervisionVisit::where('school_id', $schoolId)
            ->where(function (Builder $q) use ($classIds, $schoolId) {
                $q->whereHas('studentPkls.student', fn (Builder $sq) => $sq->whereIn('class_id', $classIds))
                    ->orWhere(function (Builder $legacyQ) use ($classIds, $schoolId) {
                        $legacyAdvisorTeacherIds = StudentPkl::where('school_id', $schoolId)
                            ->whereHas('student', fn (Builder $sq) => $sq->whereIn('class_id', $classIds))
                            ->whereNotNull('teacher_id')
                            ->distinct()
                            ->pluck('teacher_id');

                        $legacyQ->doesntHave('studentPkls')
                            ->whereIn('teacher_id', $legacyAdvisorTeacherIds);
                    });
            })
            ->with(['teacher.user', 'company', 'pklPeriod', 'assignment', 'studentPkls.student.user', 'studentPkls.student.class', 'studentPkls.teacher.user'])
            ->latest('visit_date')
            ->latest('id');

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        if (!empty($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Ambil seluruh riwayat supervisi sekolah untuk monitoring Admin Sekolah.
     */
    public function getVisitsForSchool(int $schoolId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SupervisionVisit::where('school_id', $schoolId)
            ->with(['teacher.user', 'company', 'pklPeriod', 'assignment', 'studentPkls.student.user', 'studentPkls.teacher.user'])
            ->latest('visit_date')
            ->latest('id');

        if (!empty($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Ambil daftar penugasan monitoring untuk Admin Sekolah atau Kajur.
     */
    public function getAssignments(int $schoolId, array $filters = [], ?Major $scopedMajor = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = SupervisionAssignment::where('school_id', $schoolId)
            ->with([
                'teacher.user',
                'teacher.headedMajors',
                'assignedBy',
                'pklPeriod',
                'major',
                'items.company',
                'items.studentPkl.student.user',
                'items.studentPkl.student.class.major',
                'items.studentPkl.teacher.user',
                'items.visit',
            ])
            ->latest('id');

        if ($scopedMajor) {
            $classIds = $scopedMajor->classes()->pluck('id');
            $query->where(function (Builder $q) use ($scopedMajor, $classIds) {
                $q->where('major_id', $scopedMajor->id)
                    ->orWhereHas('items.studentPkl.student', fn (Builder $sq) => $sq->whereIn('class_id', $classIds));
            });
        }

        if (!empty($filters['pkl_period_id'])) {
            $query->where('pkl_period_id', $filters['pkl_period_id']);
        }

        if (!empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        if (!empty($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        if (!empty($filters['company_id'])) {
            $query->whereHas('items', fn (Builder $iq) => $iq->where('company_id', $filters['company_id']));
        }

        return $query->paginate($perPage);
    }

    /**
     * Ringkasan tracker 2x monitoring (Monitoring 1 & Monitoring 2) per DU/DI untuk modal plotting Admin/Kajur.
     */
    public function getCompanyMonitoringMatrix(int $schoolId, int $periodId, ?Major $scopedMajor = null): Collection
    {
        $query = StudentPkl::where('school_id', $schoolId)
            ->where('pkl_period_id', $periodId)
            ->whereIn('status', ['assigned', 'active'])
            ->with([
                'company',
                'student.user',
                'student.class.major',
                'teacher.user',
                'supervisionAssignmentItems.assignment.teacher.user',
                'supervisionAssignmentItems.visit.teacher.user',
                'supervisionVisits' => fn ($q) => $q->whereIn('stage', self::PRIMARY_STAGES)->with('teacher.user'),
            ]);

        if ($scopedMajor) {
            $classIds = $scopedMajor->classes()->pluck('id');
            $query->whereHas('student', fn (Builder $sq) => $sq->whereIn('class_id', $classIds));
        }

        $studentPkls = $query->get();

        return $studentPkls->groupBy('company_id')->map(function (Collection $group) {
            /** @var Company $company */
            $company = $group->first()->company;

            $students = $group->map(function (StudentPkl $spkl) {
                $m1Assign = $spkl->supervisionAssignmentItems->firstWhere('stage', 'monitoring_1');
                $m2Assign = $spkl->supervisionAssignmentItems->firstWhere('stage', 'monitoring_2');
                $m1Visit = $spkl->supervisionVisits->firstWhere('stage', 'monitoring_1');
                $m2Visit = $spkl->supervisionVisits->firstWhere('stage', 'monitoring_2');

                return [
                    'student_pkl' => $spkl,
                    'monitoring_1' => $this->resolveStageState($m1Assign, $m1Visit),
                    'monitoring_2' => $this->resolveStageState($m2Assign, $m2Visit),
                ];
            })->values();

            return [
                'company' => $company,
                'students_count' => $students->count(),
                'students' => $students,
                'm1_done_count' => $students->where('monitoring_1.status', 'completed')->count(),
                'm1_assigned_count' => $students->where('monitoring_1.status', 'assigned')->count(),
                'm2_done_count' => $students->where('monitoring_2.status', 'completed')->count(),
                'm2_assigned_count' => $students->where('monitoring_2.status', 'assigned')->count(),
            ];
        })->sortBy(fn ($row) => $row['company']?->name ?? '')->values();
    }

    /**
     * Progres 2x monitoring untuk dashboard Guru Pembimbing (memperhitungkan kunjungan oleh petugas lain).
     */
    public function getDashboardProgressForTeacher(Teacher $teacher, Collection $companies, Collection $studentPkls): array
    {
        $supervisionStages = [
            'monitoring_1' => 'Monitoring 1',
            'monitoring_2' => 'Monitoring 2',
        ];

        $spklIds = $studentPkls->pluck('id')->all();

        $relevantVisits = SupervisionVisit::where('school_id', $teacher->user->school_id)
            ->whereIn('company_id', $companies->pluck('id'))
            ->where(function (Builder $q) use ($teacher, $spklIds) {
                $q->where('teacher_id', $teacher->id);
                if (!empty($spklIds)) {
                    $q->orWhereHas('studentPkls', fn (Builder $sq) => $sq->whereIn('student_pkl.id', $spklIds));
                }
            })
            ->with('teacher.user')
            ->get();

        $pendingAssignments = empty($spklIds)
            ? collect()
            : SupervisionAssignmentStudent::whereIn('student_pkl_id', $spklIds)
                ->whereNull('completed_at')
                ->with('assignment.teacher.user')
                ->get()
                ->groupBy('company_id');

        $visitsByCompany = $relevantVisits->groupBy('company_id');

        $supervisionProgress = $companies->map(function ($company) use ($visitsByCompany, $pendingAssignments, $supervisionStages, $studentPkls) {
            $visits = $visitsByCompany->get($company->id, collect());
            $done = $visits->pluck('stage')->unique();
            $doneCount = collect(array_keys($supervisionStages))->filter(fn ($s) => $done->contains($s))->count();
            $companyPendingAssignments = $pendingAssignments->get($company->id, collect());

            return [
                'company' => $company,
                'students' => $studentPkls->where('company_id', $company->id)->count(),
                'done' => $done,
                'done_count' => $doneCount,
                'last_visit' => $visits->max('visit_date'),
                'next_stage' => collect($supervisionStages)->first(fn ($label, $key) => !$done->contains($key)),
                'assigned_handlers' => $companyPendingAssignments
                    ->mapWithKeys(fn ($item) => [$item->stage => $item->assignment?->teacher?->user?->name])
                    ->filter(),
            ];
        })->sortBy('done_count')->values();

        return [
            'supervisionStages' => $supervisionStages,
            'supervisionProgress' => $supervisionProgress,
            'supervisionDone' => $supervisionProgress->sum('done_count'),
            'supervisionTarget' => $supervisionProgress->count() * count($supervisionStages),
        ];
    }

    /**
     * Cek apakah user berhak melihat / mengunduh Berita Acara kunjungan.
     */
    public function canViewVisit(User $user, SupervisionVisit $visit): bool
    {
        if ((int) $user->school_id !== (int) $visit->school_id) {
            return false;
        }

        if (in_array($user->role, ['admin-sekolah', 'kepsek'], true)) {
            return true;
        }

        $teacher = $user->teacher;
        if (!$teacher) {
            return false;
        }

        if ((int) $visit->teacher_id === (int) $teacher->id) {
            return true;
        }

        $coveredStudents = $this->getCoveredStudentPklsForVisit($visit);

        if ($coveredStudents->contains(fn (StudentPkl $spkl) => (int) $spkl->teacher_id === (int) $teacher->id)) {
            return true;
        }

        if ($user->isKajur()) {
            $headedMajorIds = $user->headed_major_ids;

            return $coveredStudents->contains(
                fn (StudentPkl $spkl) => in_array((int) $spkl->student?->class?->major_id, $headedMajorIds, true)
            );
        }

        return false;
    }

    /**
     * Ambil daftar StudentPkl yang tercakup dalam suatu kunjungan (pivot baru + fallback data historis).
     */
    public function getCoveredStudentPklsForVisit(SupervisionVisit $visit): Collection
    {
        $visit->loadMissing([
            'studentPkls.student.user',
            'studentPkls.student.class.major',
            'studentPkls.teacher.user',
        ]);

        if ($visit->studentPkls->isNotEmpty()) {
            return $visit->studentPkls;
        }

        return StudentPkl::where('teacher_id', $visit->teacher_id)
            ->where('company_id', $visit->company_id)
            ->where('pkl_period_id', $visit->pkl_period_id)
            ->with(['student.user', 'student.class.major', 'teacher.user'])
            ->get();
    }

    /**
     * Generate Dokumen Resmi PDF Berita Acara Supervisi Kunjungan.
     */
    public function generateBeritaAcaraPdf(SupervisionVisit $visit): DomPdfInstance
    {
        ini_set('memory_limit', '256M');
        if (PHP_SAPI !== 'cli') {
            @set_time_limit(120);
        }

        $fontDir = storage_path('fonts');
        if (!file_exists($fontDir)) {
            @mkdir($fontDir, 0755, true);
        }
        $tempDir = storage_path('framework/cache');
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $visit->loadMissing(['teacher.user', 'company', 'pklPeriod', 'school', 'assignment']);

        $students = $this->getCoveredStudentPklsForVisit($visit);
        $hasDifferentAdvisor = $students->contains(
            fn (StudentPkl $spkl) => (int) $spkl->teacher_id !== (int) $visit->teacher_id
        );

        $year = $visit->visit_date ? $visit->visit_date->format('Y') : date('Y');
        $month = $visit->visit_date ? $visit->visit_date->format('m') : date('m');
        $documentNumber = sprintf('BA-SPV/%s/%s/%04d', $year, $month, $visit->id);

        return Pdf::loadView('reports.supervision_visit_pdf', [
            'visit' => $visit,
            'students' => $students,
            'hasDifferentAdvisor' => $hasDifferentAdvisor,
            'documentNumber' => $documentNumber,
            'school' => $visit->school,
        ])->setPaper('a4', 'portrait');
    }

    public function buildBeritaAcaraFilename(SupervisionVisit $visit): string
    {
        $dateStr = $visit->visit_date ? $visit->visit_date->format('Ymd') : date('Ymd');
        $companySlug = Str::slug($visit->company->name ?? 'DUDI');

        return "Berita_Acara_Supervisi_{$companySlug}_{$dateStr}.pdf";
    }

    private function resolveTargetStudentPklsForAssignment(int $schoolId, int $periodId, array $data, ?Major $scopedMajor = null): Collection
    {
        $query = StudentPkl::where('school_id', $schoolId)
            ->where('pkl_period_id', $periodId)
            ->whereIn('status', ['assigned', 'active'])
            ->with('student.class');

        if (!empty($data['student_pkl_ids']) && is_array($data['student_pkl_ids'])) {
            $query->whereIn('id', array_map('intval', $data['student_pkl_ids']));
        } elseif (!empty($data['company_ids']) && is_array($data['company_ids'])) {
            $query->whereIn('company_id', array_map('intval', $data['company_ids']));
        } else {
            return collect();
        }

        if ($scopedMajor) {
            $classIds = $scopedMajor->classes()->pluck('id');
            $query->whereHas('student', fn (Builder $sq) => $sq->whereIn('class_id', $classIds));
        }

        return $query->get();
    }

    private function getVisitedStudentPklIdsForStage(array $studentPklIds, string $stage): Collection
    {
        if (empty($studentPklIds)) {
            return collect();
        }

        return DB::table('supervision_visit_students as svs')
            ->join('supervision_visits as sv', 'sv.id', '=', 'svs.supervision_visit_id')
            ->whereIn('svs.student_pkl_id', $studentPklIds)
            ->where('sv.stage', $stage)
            ->pluck('svs.student_pkl_id')
            ->unique()
            ->values();
    }

    private function pruneEmptyAssignments(Collection $assignmentIds): void
    {
        foreach ($assignmentIds as $id) {
            $assignment = SupervisionAssignment::find($id);
            if ($assignment && !$assignment->items()->exists() && !$assignment->visits()->exists()) {
                $assignment->delete();
            }
        }
    }

    private function resolveStageState(?SupervisionAssignmentStudent $assignItem, ?SupervisionVisit $visit): array
    {
        if ($assignItem && $assignItem->completed_at) {
            return [
                'status' => 'completed',
                'teacher_name' => $assignItem->visit?->teacher?->user?->name ?? $assignItem->assignment?->teacher?->user?->name,
                'visit_id' => $assignItem->supervision_visit_id,
            ];
        }

        if ($visit) {
            return [
                'status' => 'completed',
                'teacher_name' => $visit->teacher?->user?->name,
                'visit_id' => $visit->id,
            ];
        }

        if ($assignItem) {
            return [
                'status' => 'assigned',
                'teacher_name' => $assignItem->assignment?->teacher?->user?->name,
                'assignment_id' => $assignItem->supervision_assignment_id,
            ];
        }

        return [
            'status' => 'unassigned',
            'teacher_name' => null,
        ];
    }
}
