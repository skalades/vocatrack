<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Major extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'head_teacher_id',
        'code',
        'name',
        'program_duration_years',
        'default_pkl_duration_months',
    ];

    protected $casts = [
        'program_duration_years' => 'integer',
        'default_pkl_duration_months' => 'integer',
    ];

    public function isFourYearProgram(): bool
    {
        return (int) $this->program_duration_years === 4;
    }

    public function headTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'head_teacher_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_major')->withTimestamps();
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'major_teacher')->withTimestamps();
    }

    public function learningObjectives(): HasMany
    {
        return $this->hasMany(LearningObjective::class)->orderBy('sort_order')->orderBy('code');
    }
}
