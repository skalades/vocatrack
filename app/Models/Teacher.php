<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nip',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class, 'major_teacher')->withTimestamps();
    }

    public function headedMajors(): HasMany
    {
        return $this->hasMany(Major::class, 'head_teacher_id');
    }

    public function headedMajor(): ?Major
    {
        return $this->headedMajors()->first();
    }

    public function isMajorHead(?int $majorId = null): bool
    {
        if ($majorId) {
            return $this->headedMajors()->where('id', $majorId)->exists();
        }

        return $this->headedMajors()->exists();
    }

    public function homeroomClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'homeroom_teacher_id')
            ->orderByDesc('academic_year')
            ->orderBy('name');
    }

    public function primaryHomeroomClass(): ?SchoolClass
    {
        return $this->homeroomClasses()->first();
    }

    public function isHomeroomTeacher(?int $classId = null): bool
    {
        if ($classId) {
            return $this->homeroomClasses()->where('id', $classId)->exists();
        }

        return $this->homeroomClasses()->exists();
    }

    public function studentPkls(): HasMany
    {
        return $this->hasMany(StudentPkl::class);
    }

    public function supervisionVisits(): HasMany
    {
        return $this->hasMany(SupervisionVisit::class);
    }

    public function supervisionAssignments(): HasMany
    {
        return $this->hasMany(SupervisionAssignment::class);
    }

    /**
     * Terapkan filter pencarian (case-insensitive lintas PostgreSQL/MySQL/SQLite) dan jurusan untuk admin sekolah & ekspor.
     */
    public function scopeApplyAdminFilters(Builder $query, ?string $search = null, ?string $major = null): Builder
    {
        $term = trim((string) $search);
        if ($term !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($term));
            $like = "%{$escaped}%";

            $query->where(function (Builder $q) use ($like) {
                $q->whereHas('user', function (Builder $uq) use ($like) {
                    $uq->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(wa_number, \'\')) LIKE ?', [$like])
                        ->orWhereHas('profile', fn (Builder $pq) => $pq->whereRaw('LOWER(COALESCE(phone, \'\')) LIKE ?', [$like]));
                })
                    ->orWhereRaw('LOWER(COALESCE(nip, \'\')) LIKE ?', [$like])
                    ->orWhereHas('majors', function (Builder $mq) use ($like) {
                        $mq->whereRaw('LOWER(name) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(code) LIKE ?', [$like]);
                    });
            });
        }

        if ($major && $major !== 'all') {
            if ($major === 'lintas') {
                $query->doesntHave('majors');
            } else {
                $query->whereHas('majors', fn (Builder $mq) => $mq->where('majors.id', $major));
            }
        }

        return $query;
    }
}
