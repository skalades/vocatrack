<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRequest extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_pkl_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'destination_location',
        'attachment_path',
        'status',
        'reviewer_id',
        'reviewer_role',
        'reviewer_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function studentPkl(): BelongsTo
    {
        return $this->belongsTo(StudentPkl::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
