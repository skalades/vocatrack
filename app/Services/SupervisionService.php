<?php

namespace App\Services;

use App\Models\School;
use App\Models\StudentPkl;
use App\Models\SupervisionVisit;
use App\Models\Teacher;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfInstance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SupervisionService
{
    /**
     * Catat kunjungan supervisi baru oleh guru pembimbing.
     */
    public function createVisit(Teacher $teacher, array $data, ?UploadedFile $photoStudents = null, ?UploadedFile $photoSupervisor = null): SupervisionVisit
    {
        return DB::transaction(function () use ($teacher, $data, $photoStudents, $photoSupervisor) {
            $photoStudentsPath = null;
            if ($photoStudents) {
                $photoStudentsPath = $photoStudents->store('supervisions/students', 'public');
            }

            $photoSupervisorPath = null;
            if ($photoSupervisor) {
                $photoSupervisorPath = $photoSupervisor->store('supervisions/supervisors', 'public');
            }

            return SupervisionVisit::create([
                'school_id' => $teacher->user->school_id,
                'teacher_id' => $teacher->id,
                'company_id' => $data['company_id'],
                'pkl_period_id' => $data['pkl_period_id'],
                'stage' => $data['stage'],
                'visit_date' => $data['visit_date'],
                'student_progress_summary' => $data['student_progress_summary'],
                'company_feedback' => $data['company_feedback'],
                'follow_up_agreement' => $data['follow_up_agreement'] ?? null,
                'photo_with_students_path' => $photoStudentsPath,
                'photo_with_supervisor_path' => $photoSupervisorPath,
            ]);
        });
    }

    /**
     * Ambil riwayat supervisi untuk guru pembimbing.
     */
    public function getVisitsForTeacher(Teacher $teacher, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SupervisionVisit::where('teacher_id', $teacher->id)
            ->with(['company', 'pklPeriod'])
            ->latest('visit_date');

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Ambil seluruh riwayat supervisi sekolah untuk monitoring Admin Sekolah.
     */
    public function getVisitsForSchool(int $schoolId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SupervisionVisit::where('school_id', $schoolId)
            ->with(['teacher.user', 'company', 'pklPeriod'])
            ->latest('visit_date');

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
     * Generate Dokumen Resmi PDF Berita Acara Supervisi Kunjungan.
     */
    public function generateBeritaAcaraPdf(SupervisionVisit $visit): DomPdfInstance
    {
        $visit->loadMissing(['teacher.user', 'company', 'pklPeriod', 'school']);

        // Ambil daftar siswa bimbingan guru pada perusahaan dan periode ini
        $students = StudentPkl::where('teacher_id', $visit->teacher_id)
            ->where('company_id', $visit->company_id)
            ->where('pkl_period_id', $visit->pkl_period_id)
            ->with(['student.user', 'student.class.major'])
            ->get();

        $year = $visit->visit_date ? $visit->visit_date->format('Y') : date('Y');
        $month = $visit->visit_date ? $visit->visit_date->format('m') : date('m');
        $documentNumber = sprintf("BA-SPV/%s/%s/%04d", $year, $month, $visit->id);

        return Pdf::loadView('reports.supervision_visit_pdf', [
            'visit' => $visit,
            'students' => $students,
            'documentNumber' => $documentNumber,
            'school' => $visit->school,
        ])->setPaper('a4', 'portrait');
    }
}
