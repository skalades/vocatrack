<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\SchoolSetting;
use App\Models\StudentPkl;
use Illuminate\Support\Facades\DB;

class AssessmentService
{
    /**
     * Atomically save supervisor assessment with concurrency protection
     */
    public function saveSupervisorAssessment(StudentPkl $spkl, array $data): Assessment
    {
        return DB::transaction(function () use ($spkl, $data) {
            $assessment = Assessment::where('student_pkl_id', $spkl->id)
                ->lockForUpdate()
                ->first();

            if (!$assessment) {
                try {
                    $assessment = Assessment::create([
                        'school_id' => $spkl->school_id,
                        'student_pkl_id' => $spkl->id,
                        'score_dudi_discipline' => $data['score_dudi_discipline'],
                        'score_dudi_technical' => $data['score_dudi_technical'],
                        'score_dudi_initiative' => $data['score_dudi_initiative'],
                        'score_teacher_report' => 0.00,
                        'notes_dudi' => $data['notes_dudi'] ?? null,
                        'final_score' => 0.00,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Handled race condition: another concurrent process inserted first
                    $assessment = Assessment::where('student_pkl_id', $spkl->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $assessment->update([
                        'score_dudi_discipline' => $data['score_dudi_discipline'],
                        'score_dudi_technical' => $data['score_dudi_technical'],
                        'score_dudi_initiative' => $data['score_dudi_initiative'],
                        'notes_dudi' => $data['notes_dudi'] ?? null,
                    ]);
                }
            } else {
                $assessment->update([
                    'score_dudi_discipline' => $data['score_dudi_discipline'],
                    'score_dudi_technical' => $data['score_dudi_technical'],
                    'score_dudi_initiative' => $data['score_dudi_initiative'],
                    'notes_dudi' => $data['notes_dudi'] ?? null,
                ]);
            }

            $assessment->final_score = $this->calculateFinalScore($assessment);
            $assessment->save();

            return $assessment;
        });
    }

    /**
     * Atomically save teacher assessment with concurrency protection
     */
    public function saveTeacherAssessment(StudentPkl $spkl, array $data): Assessment
    {
        return DB::transaction(function () use ($spkl, $data) {
            $assessment = Assessment::where('student_pkl_id', $spkl->id)
                ->lockForUpdate()
                ->first();

            if (!$assessment) {
                try {
                    $assessment = Assessment::create([
                        'school_id' => $spkl->school_id,
                        'student_pkl_id' => $spkl->id,
                        'score_dudi_discipline' => 0.00,
                        'score_dudi_technical' => 0.00,
                        'score_dudi_initiative' => 0.00,
                        'score_teacher_report' => $data['score_teacher_report'],
                        'notes_teacher' => $data['notes_teacher'] ?? null,
                        'final_score' => 0.00,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Handled race condition: another concurrent process inserted first
                    $assessment = Assessment::where('student_pkl_id', $spkl->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $assessment->update([
                        'score_teacher_report' => $data['score_teacher_report'],
                        'notes_teacher' => $data['notes_teacher'] ?? null,
                    ]);
                }
            } else {
                $assessment->update([
                    'score_teacher_report' => $data['score_teacher_report'],
                    'notes_teacher' => $data['notes_teacher'] ?? null,
                ]);
            }

            $assessment->final_score = $this->calculateFinalScore($assessment);
            $assessment->save();

            return $assessment;
        });
    }

    public function calculateFinalScore(Assessment $assessment): float
    {
        $schoolId = $assessment->school_id;

        $weightDudi = (float) (SchoolSetting::where('school_id', $schoolId)->where('key', 'weight_dudi')->value('value') ?? 60);
        $weightGuru = (float) (SchoolSetting::where('school_id', $schoolId)->where('key', 'weight_guru')->value('value') ?? 40);

        // Normalize weights if sum != 100
        $sum = $weightDudi + $weightGuru;
        if ($sum > 0) {
            $wDudi = $weightDudi / $sum;
            $wGuru = $weightGuru / $sum;
        } else {
            $wDudi = 0.6;
            $wGuru = 0.4;
        }

        $avgDudi = ((float) $assessment->score_dudi_discipline + (float) $assessment->score_dudi_technical + (float) $assessment->score_dudi_initiative) / 3;
        $avgGuru = (float) $assessment->score_teacher_report;

        $finalScore = ($avgDudi * $wDudi) + ($avgGuru * $wGuru);

        return round($finalScore, 2);
    }
}
