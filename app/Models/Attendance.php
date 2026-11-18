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
        'checkout_photo_path',
        'checkout_lat',
        'checkout_lng',
        'is_checkout_within_radius',
        'checkout_accuracy_meters',
        'is_checkout_mock_detected',
        'checkout_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'is_within_radius' => 'boolean',
        'accuracy_meters' => 'integer',
        'is_mock_detected' => 'boolean',
        'checkin_lat' => 'float',
        'checkin_lng' => 'float',
        'is_checkout_within_radius' => 'boolean',
        'checkout_accuracy_meters' => 'integer',
        'is_checkout_mock_detected' => 'boolean',
        'checkout_lat' => 'float',
        'checkout_lng' => 'float',
    ];

    /**
     * URL terproteksi untuk selfie presensi masuk (disajikan dari disk privat).
     */
    public function getCheckinPhotoUrlAttribute(): ?string
    {
        return $this->checkin_photo_path
            ? route('secure.file', ['type' => 'presensi', 'id' => $this->id])
            : null;
    }

    /**
     * URL terproteksi untuk selfie presensi pulang (disajikan dari disk privat).
     */
    public function getCheckoutPhotoUrlAttribute(): ?string
    {
        return $this->checkout_photo_path
            ? route('secure.file', ['type' => 'presensi-pulang', 'id' => $this->id])
            : null;
    }

    public function studentPkl(): BelongsTo
    {
        return $this->belongsTo(StudentPkl::class);
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Student::class,
            StudentPkl::class,
            'id',
            'id',
            'student_pkl_id',
            'student_id'
        );
    }

    public function getCheckInAtAttribute(): ?string
    {
        return $this->checkin_time;
    }

    public function getCheckOutAtAttribute(): ?string
    {
        return $this->checkout_time;
    }
}
