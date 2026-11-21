<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'created_by_user_id',
        'name',
        'address',
        'contact_person',
        'phone',
        'gps_enabled',
        'latitude',
        'longitude',
        'radius_meters',
        'override_hours',
        'custom_work_hours',
        'work_days',
        'default_start_date',
        'default_end_date',
        'requires_industry_approval',
        'is_pkl_started',
    ];

    protected $attributes = [
        'is_pkl_started' => false,
        'requires_industry_approval' => true,
    ];

    protected $casts = [
        'default_start_date' => 'date',
        'default_end_date' => 'date',
        'requires_industry_approval' => 'boolean',
        'is_pkl_started' => 'boolean',
        'gps_enabled' => 'boolean',
        'override_hours' => 'boolean',
        'custom_work_hours' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function supervisors(): HasMany
    {
        return $this->hasMany(Supervisor::class);
    }

    public function studentPkls(): HasMany
    {
        return $this->hasMany(StudentPkl::class);
    }

    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class, 'company_major')->withTimestamps();
    }

    /**
     * Check if a given date is an active work day for this company.
     * Supports:
     * - '7_days': 7 days full (Monday - Sunday / On-Project)
     * - '6_days': 6 days (Monday - Saturday)
     * - '5_days': 5 days (Monday - Friday)
     */
    public function isWorkDay(mixed $date = null): bool
    {
        $dateObj = $date ? ($date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date)) : \Carbon\Carbon::today();
        $dayOfWeek = $dateObj->dayOfWeekIso; // 1 (Mon) - 7 (Sun)

        return match ($this->work_days) {
            '7_days', '7_hari' => true,
            '6_days', '6_hari' => $dayOfWeek <= 6,
            default => $dayOfWeek <= 5,
        };
    }

    /**
     * Check if this company operates through holidays (e.g. 7 days full on-project).
     */
    public function worksOnHolidays(): bool
    {
        return in_array($this->work_days, ['7_days', '7_hari'], true);
    }

    /**
     * Human-readable label for company work days schedule.
     */
    public function getWorkDaysLabelAttribute(): string
    {
        return match ($this->work_days) {
            '7_days', '7_hari' => '7 Hari (On-Project)',
            '6_days', '6_hari' => '6 Hari (Sen-Sab)',
            default => '5 Hari (Sen-Jum)',
        };
    }
}
