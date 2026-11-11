<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_pkl_id',
        'date',
        'status',
        'checkin_time',
        'checkin_photo_path',
        'checkin_lat',
        'checkin_lng',
        'is_within_radius',
        'accuracy_meters',
        'is_mock_detected',
        'checkout_time',
        'checkout_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'is_within_radius' => 'boolean',
        'accuracy_meters' => 'integer',
        'is_mock_detected' => 'boolean',
        'checkin_lat' => 'float',
        'checkin_lng' => 'float',
    ];

    public function studentPkl(): BelongsTo
    {
        return $this->belongsTo(StudentPkl::class);
    }
}
