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
        'storage_used_bytes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'storage_used_bytes' => 'integer',
    ];

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

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

    /**
     * Check if a specific SaaS feature is enabled on the school's active subscription plan.
     */
    public function hasFeature(string $feature): bool
    {
        $sub = $this->activeSubscription();
        if (!$sub) {
            return true; // Backward compatibility for unseeded or manual test schools
        }

        if ($sub->isExpired() && !$sub->isGracePeriod()) {
            return false;
        }

        $plan = $sub->plan;
        if (!$plan) {
            return true;
        }

        $features = $plan->features ?? [];
        if (!array_key_exists($feature, $features)) {
            return true;
        }

        return (bool) $features[$feature];
    }

    /**
     * Get array of all features for the school's active plan.
     */
    public function getActiveFeaturesAttribute(): array
    {
        $sub = $this->activeSubscription();
        if (!$sub || !$sub->plan) {
            return [
                'attendance' => true,
                'journal' => true,
                'assessment' => true,
                'certificate' => true,
                'whatsapp_gateway' => false,
                'custom_domain' => false,
            ];
        }

        return $sub->plan->features ?? [];
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

    public function getStorageUsedMbAttribute(): float
    {
        return round(($this->storage_used_bytes ?? 0) / (1024 * 1024), 2);
    }

    public function getStorageQuotaMbAttribute(): int
    {
        return $this->activeSubscription()?->storage_quota_mb ?? 2048;
    }

    public function getStorageUsagePercentAttribute(): int
    {
        $quota = max(1, $this->storage_quota_mb);
        return min(100, (int) round(($this->storage_used_mb / $quota) * 100));
    }
}
