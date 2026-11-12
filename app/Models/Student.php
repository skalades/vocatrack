<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'class_id',
        'nis',
        'nisn',
        'parent_token',
        'is_parent_portal_active',
    ];

    protected $casts = [
        'is_parent_portal_active' => 'boolean',
    ];

    protected $attributes = [
        'is_parent_portal_active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (Student $student) {
            if (empty($student->parent_token)) {
                $student->parent_token = Str::random(32);
            }
            if (is_null($student->is_parent_portal_active)) {
                $student->is_parent_portal_active = true;
            }
        });
    }

    public function regenerateParentToken(): string
    {
        $newToken = Str::random(32);
        $this->update(['parent_token' => $newToken]);

        return $newToken;
    }

    public function getParentMonitoringUrlAttribute(): ?string
    {
        if (empty($this->parent_token)) {
            return null;
        }

        return route('parent.monitor', ['token' => $this->parent_token]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function studentPkls(): HasMany
    {
        return $this->hasMany(StudentPkl::class);
    }

    public function activePkl(): HasOne
    {
        return $this->hasOne(StudentPkl::class)->where('status', 'active');
    }
}
