<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'price',
        'billing_period',
        'max_students',
        'max_storage_mb',
        'features',
        'description',
        'is_popular',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'max_students' => 'integer',
        'max_storage_mb' => 'integer',
        'features' => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SchoolSubscription::class, 'plan_id');
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getPeriodLabelAttribute(): string
    {
        return match ($this->billing_period) {
            'monthly' => '/ Bulan',
            'semester' => '/ Semester (6 Bln)',
            'yearly' => '/ Tahun',
            default => '',
        };
    }
}
