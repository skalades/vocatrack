<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasPushSubscriptions;

    public const DEFAULT_PASSWORD = 'password';

    protected $fillable = [
        'school_id',
        'name',
        'email',
        'wa_number',
        'password',
        'role',
        'is_active',
        'must_change_password',
        'notification_preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'notification_preferences' => 'array',
        ];
    }

    public function getFormattedWaNumberAttribute(): ?string
    {
        $phone = $this->wa_number ?: ($this->profile?->phone ?? null);
        return \App\Services\WhatsAppGatewayService::normalizePhoneNumber($phone);
    }

    /**
     * Cek apakah user bersedia menerima notifikasi di channel tertentu.
     * Catatan: Notifikasi darurat/SOS tidak dapat dimatikan (selalu true).
     */
    public function wantsChannel(string $channel, string $type = 'general'): bool
    {
        if (in_array($type, ['sos', 'emergency_incident'], true)) {
            return true;
        }

        if (empty($this->notification_preferences)) {
            return true;
        }

        return (bool) ($this->notification_preferences[$type][$channel] ?? true);
    }

    public function wantsWhatsAppNotification(string $type = 'general'): bool
    {
        return $this->wantsChannel('whatsapp', $type);
    }

    public function wantsMailNotification(string $type = 'general'): bool
    {
        return $this->wantsChannel('mail', $type);
    }

    public function isKajur(?int $majorId = null): bool
    {
        return (bool) $this->teacher?->isMajorHead($majorId);
    }

    public function headedMajor(): ?Major
    {
        return $this->teacher?->headedMajor();
    }

    public function getHeadedMajorIdsAttribute(): array
    {
        return $this->teacher?->headedMajors()->pluck('id')->toArray() ?? [];
    }

    public function isWaliKelas(?int $classId = null): bool
    {
        return (bool) $this->teacher?->isHomeroomTeacher($classId);
    }

    public function homeroomClasses(): Collection
    {
        return $this->teacher?->homeroomClasses()->with('major')->get() ?? collect();
    }

    public function primaryHomeroomClass(): ?SchoolClass
    {
        return $this->teacher?->primaryHomeroomClass();
    }

    public function getHomeroomClassIdsAttribute(): array
    {
        return $this->teacher?->homeroomClasses()->pluck('id')->map(fn ($id) => (int) $id)->toArray() ?? [];
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles, true);
        }
        return $this->role === $roles;
    }

    /**
     * Periksa apakah pengguna masih memakai kata sandi default sistem ('password').
     */
    public function isUsingDefaultPassword(): bool
    {
        return filled($this->password) && Hash::check(self::DEFAULT_PASSWORD, $this->password);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function supervisor(): HasOne
    {
        return $this->hasOne(Supervisor::class);
    }

    public function announcementReads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }
}
