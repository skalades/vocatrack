<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    /** Tipe tagihan yang menerbitkan periode langganan baru ketika lunas. */
    public const PERIOD_TYPES = ['activation', 'renewal', 'upgrade'];

    public const TYPE_LABELS = [
        'activation' => 'Aktivasi',
        'renewal' => 'Perpanjangan',
        'upgrade' => 'Upgrade Paket',
        'manual' => 'Tagihan Lain',
    ];

    public const STATUS_LABELS = [
        'unpaid' => 'Belum Dibayar',
        'pending_verification' => 'Menunggu Verifikasi',
        'paid' => 'Lunas',
        'cancelled' => 'Dibatalkan',
    ];

    protected $fillable = [
        'invoice_number',
        'school_id',
        'subscription_id',
        'type',
        'plan_id',
        'period_months',
        'student_quota',
        'storage_quota_mb',
        'title',
        'amount',
        'due_date',
        'status',
        'payment_method',
        'payment_proof_path',
        'paid_at',
        'applied_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'applied_at' => 'datetime',
        'period_months' => 'integer',
        'student_quota' => 'integer',
        'storage_quota_mb' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(SchoolSubscription::class, 'subscription_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['unpaid', 'pending_verification'], true);
    }

    public function createsPeriod(): bool
    {
        return in_array($this->type, self::PERIOD_TYPES, true) && $this->period_months > 0;
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_date && $this->due_date->lt(today());
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst((string) $this->type);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }
}
