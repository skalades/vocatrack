<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_pkl_id',
        'date',
        'activity_description',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function studentPkl(): BelongsTo
    {
        return $this->belongsTo(StudentPkl::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(JournalPhoto::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(JournalApproval::class)->orderBy('created_at', 'desc');
    }
}
