<?php

namespace Database\Seeders;

use App\Models\PklPeriod;
use App\Models\School;
use App\Models\StudentPkl;
use App\Models\SupervisionAssignment;
use App\Models\SupervisionAssignmentStudent;
use App\Models\SupervisionVisit;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonitoringOneSeeder extends Seeder
{
    /**
     * Seed contoh data Monitoring 1 (monitoring_1) yang siap cetak/ekspor Berita Acara PDF.
     *
     * Aman dijalankan berulang kali (idempoten): hanya memproses siswa/DU/DI aktif
     * yang belum memiliki data Monitoring 1.
     *
     * Pemakaian:
     *   php artisan db:seed --class=MonitoringOneSeeder
     */
    public function run(): void
    {
        $schools = School::where('is_active', true)->get();

        if ($schools->isEmpty()) {
            $this->command?->warn('Tidak ada sekolah aktif ditemukan. Seeder Monitoring 1 dilewati.');

            return;
        }

        $totalVisitsCreated = 0;
        $totalStudentsCovered = 0;

        foreach ($schools as $school) {
            $result = $this->seedForSchool($school);
            $totalVisitsCreated += $result['visits'];
            $totalStudentsCovered += $result['students'];
        }

        $this->command?->info("MonitoringOneSeeder selesai: {$totalVisitsCreated} kunjungan Monitoring 1 dibuat ({$totalStudentsCovered} siswa tercakup) dan siap ekspor PDF.");
    }

    /**
     * @return array{visits: int, students: int}
     */
    private function seedForSchool(School $school): array
    {
        $activePeriod = PklPeriod::where('school_id', $school->id)
            ->where('is_active', true)
            ->latest('id')
            ->first()
            ?? PklPeriod::where('school_id', $school->id)->latest('id')->first();

        if (!$activePeriod) {
            return ['visits' => 0, 'students' => 0];
        }

        $teachers = Teacher::whereHas('user', fn ($q) => $q->where('school_id', $school->id)->where('is_active', true))
            ->with(['user', 'headedMajors'])
            ->get();

        if ($teachers->isEmpty()) {
            return ['visits' => 0, 'students' => 0];
        }

        $adminUser = User::where('school_id', $school->id)
            ->where('role', 'admin-sekolah')
            ->first();

        // Ambil penempatan aktif pada periode ini yang belum pernah masuk penugasan/kunjungan monitoring_1
        $alreadyAssignedIds = SupervisionAssignmentStudent::where('pkl_period_id', $activePeriod->id)
            ->where('stage', 'monitoring_1')
            ->pluck('student_pkl_id')
            ->all();

        $alreadyVisitedIds = DB::table('supervision_visit_students as svs')
            ->join('supervision_visits as sv', 'sv.id', '=', 'svs.supervision_visit_id')
            ->where('sv.school_id', $school->id)
            ->where('sv.pkl_period_id', $activePeriod->id)
            ->where('sv.stage', 'monitoring_1')
            ->pluck('svs.student_pkl_id')
            ->all();

        $excludedIds = array_unique(array_merge($alreadyAssignedIds, $alreadyVisitedIds));

        $eligiblePkls = StudentPkl::where('school_id', $school->id)
            ->where('pkl_period_id', $activePeriod->id)
            ->whereIn('status', ['assigned', 'active'])
            ->whereNotNull('company_id')
            ->whereNotNull('teacher_id')
            ->when(!empty($excludedIds), fn ($q) => $q->whereNotIn('id', $excludedIds))
            ->with(['company', 'teacher.user', 'student.user', 'student.class.major'])
            ->get();

        if ($eligiblePkls->isEmpty()) {
            return ['visits' => 0, 'students' => 0];
        }

        $byCompany = $eligiblePkls->groupBy('company_id')->take(3)->values();
        $visitsCreated = 0;
        $studentsCovered = 0;
        $visitDate = $this->resolveMonitoringDate($activePeriod);

        return DB::transaction(function () use (
            $school,
            $activePeriod,
            $teachers,
            $adminUser,
            $byCompany,
            $visitDate,
            &$visitsCreated,
            &$studentsCovered
        ) {
            // Skenario 1: Penugasan lintas pembimbing (1 petugas ke 1-2 perusahaan sekaligus)
            $delegatedGroups = $byCompany->take(2);
            if ($delegatedGroups->isNotEmpty()) {
                $firstAdvisorIds = $delegatedGroups->flatten(1)->pluck('teacher_id')->unique()->all();
                $officer = $teachers->first(fn (Teacher $t) => $t->headedMajors->isNotEmpty() && !in_array($t->id, $firstAdvisorIds, true))
                    ?? $teachers->first(fn (Teacher $t) => !in_array($t->id, $firstAdvisorIds, true))
                    ?? $teachers->first();

                $assignment = SupervisionAssignment::create([
                    'school_id' => $school->id,
                    'pkl_period_id' => $activePeriod->id,
                    'teacher_id' => $officer->id,
                    'assigned_by' => $adminUser?->id,
                    'stage' => 'monitoring_1',
                    'assignment_number' => sprintf('ST-MON1/%s/%03d', $visitDate->format('Y'), $school->id),
                    'scheduled_date' => $visitDate->toDateString(),
                    'notes' => 'Monitoring 1 awal pelaksanaan PKL (data seeder siap ekspor Berita Acara PDF).',
                ]);

                foreach ($delegatedGroups as $idx => $companyStudents) {
                    $visit = $this->createMonitoringOneVisit(
                        $school->id,
                        $officer->id,
                        (int) $companyStudents->first()->company_id,
                        $activePeriod->id,
                        $assignment->id,
                        $visitDate->copy()->addDays($idx),
                        $companyStudents,
                        true
                    );

                    foreach ($companyStudents as $spkl) {
                        SupervisionAssignmentStudent::create([
                            'supervision_assignment_id' => $assignment->id,
                            'student_pkl_id' => $spkl->id,
                            'company_id' => $spkl->company_id,
                            'pkl_period_id' => $activePeriod->id,
                            'stage' => 'monitoring_1',
                            'supervision_visit_id' => $visit->id,
                            'completed_at' => now(),
                        ]);
                    }

                    $visitsCreated++;
                    $studentsCovered += $companyStudents->count();
                }
            }

            // Skenario 2: Monitoring 1 langsung oleh Guru Pembimbing Asli (tanpa pengalihan)
            $directGroup = $byCompany->get(2);
            if ($directGroup && $directGroup->isNotEmpty()) {
                $byAdvisor = $directGroup->groupBy('teacher_id');
                foreach ($byAdvisor as $advisorId => $advisorStudents) {
                    $this->createMonitoringOneVisit(
                        $school->id,
                        (int) $advisorId,
                        (int) $advisorStudents->first()->company_id,
                        $activePeriod->id,
                        null,
                        $visitDate->copy()->addDays(2),
                        $advisorStudents,
                        false
                    );

                    $visitsCreated++;
                    $studentsCovered += $advisorStudents->count();
                }
            }

            return ['visits' => $visitsCreated, 'students' => $studentsCovered];
        });
    }

    private function createMonitoringOneVisit(
        int $schoolId,
        int $visitorTeacherId,
        int $companyId,
        int $periodId,
        ?int $assignmentId,
        Carbon $visitDate,
        Collection $studentPkls,
        bool $isDelegated
    ): SupervisionVisit {
        $companyName = $studentPkls->first()?->company?->name ?? 'Mitra DU/DI';

        $progressSummary = $isDelegated
            ? "Pelaksanaan Monitoring 1 di {$companyName} berjalan tertib. Peserta didik menunjukkan tingkat kedisiplinan, kehadiran, serta kepatuhan terhadap SOP dan budaya kerja industri dengan sangat baik pada bulan pertama."
            : "Kunjungan Monitoring 1 bersama pembimbing lapangan {$companyName}. Seluruh peserta didik telah beradaptasi dengan alur kerja divisi penempatan dan aktif mengisi jurnal kegiatan harian.";

        $companyFeedback = "Pembimbing industri {$companyName} mengapresiasi etos kerja, sikap sopan santun, dan kecepatan belajar siswa dalam memahami tugas operasional dasar serta penerapan K3 di tempat kerja.";

        $followUp = 'Pemantapan kompetensi inti sesuai Capaian Pembelajaran (CP) dan evaluasi berkala jurnal harian menjelang Monitoring 2.';

        $visit = SupervisionVisit::create([
            'school_id' => $schoolId,
            'teacher_id' => $visitorTeacherId,
            'company_id' => $companyId,
            'pkl_period_id' => $periodId,
            'supervision_assignment_id' => $assignmentId,
            'stage' => 'monitoring_1',
            'visit_date' => $visitDate->toDateString(),
            'student_progress_summary' => $progressSummary,
            'company_feedback' => $companyFeedback,
            'follow_up_agreement' => $followUp,
        ]);

        $visit->studentPkls()->syncWithoutDetaching($studentPkls->pluck('id')->all());

        return $visit;
    }

    private function resolveMonitoringDate(PklPeriod $period): Carbon
    {
        $start = $period->start_date ? Carbon::parse($period->start_date) : Carbon::today()->subMonth();
        $candidate = $start->copy()->addMonth();

        return $candidate->isFuture() ? Carbon::today() : $candidate;
    }
}
