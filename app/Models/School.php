<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'npsn',
        'name',
        'address',
        'phone',
        'email',
        'logo_path',
        'stamp_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function settings(): HasMany
    {
        return $this->hasMany(SchoolSetting::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function pklPeriods(): HasMany
    {
        return $this->hasMany(PklPeriod::class);
    }

    public function studentPkls(): HasMany
    {
        return $this->hasMany(StudentPkl::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SchoolSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->subscriptions()->latest('end_date')->first();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getStudentsCountAttribute(): int
    {
        return Student::whereHas('class', function ($q) {
            $q->where('school_id', $this->id);
        })->count();
    }

    public function getSetting(string $key, $default = null)
    {
        $setting = $this->settings()->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
