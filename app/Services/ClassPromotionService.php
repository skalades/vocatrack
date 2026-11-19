<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClassPromotionService
{
    /**
     * Preview simulasi pergantian kelas dan kelulusan tahun ajaran berikutnya.
     */
    public function previewPromotion(int $schoolId, string $currentAcademicYear, string $targetAcademicYear): array
    {
        $classes = SchoolClass::where('school_id', $schoolId)
            ->where('academic_year', $currentAcademicYear)
            ->with(['major', 'students' => fn($q) => $q->where('academic_status', 'active')])
            ->get();

        $preview = [];

        foreach ($classes as $class) {
            $studentCount = $class->students->count();
            $nextGrade = $class->getNextGradeLevel();
            $nextClassName = $class->generateNextClassName();
            $isFourYear = (bool) $class->major?->isFourYearProgram();

            if ($nextGrade && $nextClassName) {
                $actionType = 'promote';
                $actionLabel = $nextGrade === 'XIII'
                    ? "Naik ke Kelas XIII (Program 4 Tahun {$class->major?->code})"
                    : "Naik ke Tingkat {$nextGrade}";
                $targetName = $nextClassName;
            } else {
                $actionType = 'graduate';
                $actionLabel = 'Lulus (Alumni)';
                $targetName = 'Alumni / Selesai Studi';
            }

            $preview[] = [
                'class_id' => $class->id,
                'class_name' => $class->name,
                'grade_level' => $class->grade_level,
                'major_name' => $class->major?->name ?? '-',
                'major_code' => $class->major?->code ?? '-',
                'is_four_year' => $isFourYear,
                'student_count' => $studentCount,
                'action_type' => $actionType,
                'action_label' => $actionLabel,
                'target_grade' => $nextGrade,
                'target_class_name' => $targetName,
                'target_academic_year' => $targetAcademicYear,
            ];
        }

        return $preview;
    }

    /**
     * Eksekusi pergantian kelas dan kelulusan secara otomatis dan aman (DB Transaction).
     */
    public function executePromotion(
        int $schoolId,
        string $currentAcademicYear,
        string $targetAcademicYear,
        array $selectedClassIds = []
    ): array {
        return DB::transaction(function () use ($schoolId, $currentAcademicYear, $targetAcademicYear, $selectedClassIds) {
            $query = SchoolClass::where('school_id', $schoolId)
                ->where('academic_year', $currentAcademicYear)
                ->with(['major', 'students' => fn($q) => $q->where('academic_status', 'active')]);

            if (!empty($selectedClassIds)) {
                $query->whereIn('id', $selectedClassIds);
            }

            $classes = $query->get();

            $stats = [
                'promoted_classes_count' => 0,
                'new_classes_created' => 0,
                'promoted_students_count' => 0,
                'graduated_students_count' => 0,
                'details' => [],
            ];

            // Ekstrak tahun awal untuk graduation year, misal "2027/2028" -> "2027"
            $gradYear = substr($targetAcademicYear, 0, 4);

            foreach ($classes as $class) {
                $activeStudents = $class->students;
                if ($activeStudents->isEmpty()) {
                    continue;
                }

                $nextGrade = $class->getNextGradeLevel();
                $nextClassName = $class->generateNextClassName();

                if ($nextGrade && $nextClassName) {
                    // Cari atau buat kelas target untuk tahun ajaran baru
                    $targetClass = SchoolClass::firstOrCreate(
                        [
                            'school_id' => $schoolId,
                            'major_id' => $class->major_id,
                            'name' => $nextClassName,
                            'academic_year' => $targetAcademicYear,
                        ],
                        [
                            'grade_level' => $nextGrade,
                        ]
                    );

                    if ($targetClass->wasRecentlyCreated) {
                        $stats['new_classes_created']++;
                    }

                    // Pindahkan siswa aktif ke kelas target baru
                    $studentIds = $activeStudents->pluck('id')->toArray();
                    Student::whereIn('id', $studentIds)->update([
                        'class_id' => $targetClass->id,
                    ]);

                    $count = count($studentIds);
                    $stats['promoted_students_count'] += $count;
                    $stats['promoted_classes_count']++;
                    $stats['details'][] = "{$class->name} ({$class->academic_year}) -> {$targetClass->name} ({$targetAcademicYear}): {$count} siswa naik kelas.";
                } else {
                    // Siswa kelas akhir dinyatakan lulus
                    $studentIds = $activeStudents->pluck('id')->toArray();
                    Student::whereIn('id', $studentIds)->update([
                        'academic_status' => 'graduated',
                        'graduation_year' => $gradYear,
                    ]);

                    $count = count($studentIds);
                    $stats['graduated_students_count'] += $count;
                    $stats['details'][] = "{$class->name} ({$class->academic_year}): {$count} siswa dinyatakan lulus (alumni).";
                }
            }

            Log::info("Kenaikan kelas otomatis berhasil dijalankan di Sekolah ID {$schoolId}", $stats);

            return $stats;
        });
    }
}
