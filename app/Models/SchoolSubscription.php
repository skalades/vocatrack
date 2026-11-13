<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'plan_id',
        'plan_name',
        'start_date',
        'end_date',
        'price_paid',
        'student_quota',
        'storage_quota_mb',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price_paid' => 'decimal:2',
        'student_quota' => 'integer',
        'storage_quota_mb' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'subscription_id');
    }

    public function daysRemaining(): int
    {
        $today = Carbon::today();
        if ($this->end_date->isPast()) {
            return -$today->diffInDays($this->end_date);
        }
        return $today->diffInDays($this->end_date);
    }

    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    public function isGracePeriod(): bool
    {
        return $this->status === 'grace_period' || ($this->isExpired() && $this->daysRemaining() >= -7);
    }

    public function getComputedStatusAttribute(): string
    {
        if ($this->status === 'suspended') {
            return 'suspended';
        }

        if ($this->end_date->isPast()) {
            // within 7 days of expiration = grace period
            if ($this->daysRemaining() >= -7) {
                return 'grace_period';
            }
            return 'expired';
        }

        if ($this->status === 'trial') {
            return 'trial';
        }

        if ($this->daysRemaining() <= 14) {
            return 'expiring_soon';
        }

        return 'active';
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->price_paid <= 0) {
            return 'Gratis / Trial';
        }
        return 'Rp ' . number_format($this->price_paid, 0, ',', '.');
    }
}
