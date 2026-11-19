<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentPkl extends Model
{
    use HasFactory, BelongsToSchool;

    protected $table = 'student_pkl';

    protected $fillable = [
        'school_id',
        'student_id',
        'company_id',
        'pkl_period_id',
        'teacher_id',
        'supervisor_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function getEffectiveStartDateAttribute(): ?\Carbon\Carbon
    {
        return $this->start_date ? \Carbon\Carbon::parse($this->start_date) : $this->pklPeriod?->start_date;
    }

    public function getEffectiveEndDateAttribute(): ?\Carbon\Carbon
    {
        return $this->end_date ? \Carbon\Carbon::parse($this->end_date) : $this->pklPeriod?->end_date;
    }

    /**
     * Cek apakah pelaksanaan PKL saat ini sudah dimulai oleh pihak perusahaan (is_pkl_started).
     */
    public function getIsStartedAttribute(): bool
    {
        return (bool) ($this->company?->is_pkl_started ?? false);
    }

    /**
     * Status terhitung penugasan siswa (mempertimbangkan pengaturan mulai dari perusahaan mitra).
     */
    public function getComputedStatusAttribute(): string
    {
        if (in_array($this->status, ['completed', 'withdrawn'], true)) {
            return $this->status;
        }

        return $this->is_started ? 'active' : 'pending_start';
    }

    /**
     * Label teks status penugasan siswa untuk tampilan UI.
     */
    public function getComputedStatusLabelAttribute(): string
    {
        return match ($this->computed_status) {
            'completed' => 'SELESAI',
            'withdrawn' => 'DIBATALKAN',
            'active' => 'AKTIF',
            default => 'BELUM MULAI',
        };
    }

    public function isPklActiveToday(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $today = \Carbon\Carbon::today();
        $start = $this->effective_start_date;
        $end = $this->effective_end_date;

        if ($start && $today->lt($start)) {
            return false;
        }

        if ($end && $today->gt($end)) {
            return false;
        }

        return true;
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pklPeriod(): BelongsTo
    {
        return $this->belongsTo(PklPeriod::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceRequests(): HasMany
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(Assessment::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }
}
