<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_pkl_id',
        'uuid',
        'certificate_number',
        'final_score',
        'final_predicate',
        'issued_date',
        'pdf_path',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'final_score' => 'float',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentPkl(): BelongsTo
    {
        return $this->belongsTo(StudentPkl::class);
    }

    public function getVerificationUrlAttribute(): string
    {
        return url('/verify/cert/' . $this->uuid);
    }

    public function getPredicateBadgeClassAttribute(): string
    {
        return match ($this->final_predicate) {
            'Sangat Baik' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 border-emerald-200',
            'Baik' => 'bg-blue-50 text-blue-700 ring-blue-600/20 border-blue-200',
            'Cukup' => 'bg-amber-50 text-amber-700 ring-amber-600/20 border-amber-200',
            'Kurang' => 'bg-rose-50 text-rose-700 ring-rose-600/20 border-rose-200',
            default => 'bg-slate-50 text-slate-700 ring-slate-600/20 border-slate-200',
        };
    }
}
