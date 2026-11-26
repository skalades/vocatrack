<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupervisionAssignment extends Model
{
    use HasFactory, BelongsToSchool;

    public const STAGES = [
        'monitoring_1' => 'Monitoring 1 (Bulan Ke-1)',
        'monitoring_2' => 'Monitoring 2 (Pertengahan)',
    ];

    protected $fillable = [
        'school_id',
        'pkl_period_id',
        'teacher_id',
        'assigned_by',
        'major_id',
        'stage',
        'assignment_number',
        'scheduled_date',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function pklPeriod(): BelongsTo
    {
        return $this->belongsTo(PklPeriod::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupervisionAssignmentStudent::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(SupervisionVisit::class);
    }

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage] ?? ucwords(str_replace('_', ' ', $this->stage));
    }

    public function getStageShortLabelAttribute(): string
    {
        return match ($this->stage) {
            'monitoring_1' => 'Monitoring 1',
            'monitoring_2' => 'Monitoring 2',
            default => $this->stage_label,
        };
    }

    public function getStageBadgeClassAttribute(): string
    {
        return match ($this->stage) {
            'monitoring_1' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 border-indigo-200',
            'monitoring_2' => 'bg-purple-50 text-purple-700 ring-purple-600/20 border-purple-200',
            default => 'bg-slate-50 text-slate-700 ring-slate-600/20 border-slate-200',
        };
    }
}
