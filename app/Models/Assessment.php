<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assessment extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_pkl_id',
        'score_dudi_discipline',
        'score_dudi_technical',
        'score_dudi_initiative',
        'score_teacher_report',
        'final_score',
        'notes_dudi',
        'notes_teacher',
    ];

    protected $casts = [
        'score_dudi_discipline' => 'float',
        'score_dudi_technical' => 'float',
        'score_dudi_initiative' => 'float',
        'score_teacher_report' => 'float',
        'final_score' => 'float',
    ];

    public function studentPkl(): BelongsTo
    {
        return $this->belongsTo(StudentPkl::class);
    }
}
