<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisionVisit extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'company_id',
        'pkl_period_id',
        'stage',
        'visit_date',
        'student_progress_summary',
        'company_feedback',
        'follow_up_agreement',
        'photo_with_students_path',
        'photo_with_supervisor_path',
    ];

    protected $casts = [
        'visit_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pklPeriod(): BelongsTo
    {
        return $this->belongsTo(PklPeriod::class);
    }

    public function getStageLabelAttribute(): string
    {
        return match ($this->stage) {
            'penyerahan' => 'Penyerahan Siswa',
            'monitoring_1' => 'Monitoring 1 (Bulan Ke-1)',
            'monitoring_2' => 'Monitoring 2 (Pertengahan)',
            'penjemputan' => 'Penjemputan Siswa',
            'insidental' => 'Kunjungan Insidental',
            default => ucwords(str_replace('_', ' ', $this->stage)),
        };
    }

    public function getStageBadgeClassAttribute(): string
    {
        return match ($this->stage) {
            'penyerahan' => 'bg-blue-50 text-blue-700 ring-blue-600/20 border-blue-200',
            'monitoring_1' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 border-indigo-200',
            'monitoring_2' => 'bg-purple-50 text-purple-700 ring-purple-600/20 border-purple-200',
            'penjemputan' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 border-emerald-200',
            'insidental' => 'bg-amber-50 text-amber-700 ring-amber-600/20 border-amber-200',
            default => 'bg-slate-50 text-slate-700 ring-slate-600/20 border-slate-200',
        };
    }
}
