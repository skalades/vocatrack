<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
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
    ];

    protected $casts = [
        'gps_enabled' => 'boolean',
        'override_hours' => 'boolean',
        'custom_work_hours' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'integer',
    ];

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
}
