<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\SchoolSetting;
use App\Models\StudentPkl;

class AssessmentService
{
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
