<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisionAssignmentStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'supervision_assignment_id',
        'student_pkl_id',
        'company_id',
        'pkl_period_id',
        'stage',
        'supervision_visit_id',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(SupervisionAssignment::class, 'supervision_assignment_id');
    }

    public function studentPkl(): BelongsTo
    {
        return $this->belongsTo(StudentPkl::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pklPeriod(): BelongsTo
    {
        return $this->belongsTo(PklPeriod::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(SupervisionVisit::class, 'supervision_visit_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
