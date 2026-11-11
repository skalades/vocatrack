<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'author_id',
        'title',
        'content',
        'category',
        'image_path',
        'target_role',
        'target_major_id',
        'is_pinned',
        'is_active',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class, 'target_major_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePinnedFirst(Builder $query): Builder
    {
        return $query->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');
    }

    /**
     * Scoping announcements specifically for a user based on role, category, and major.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        $role = $user->role;

        // Super-admin & Admin Sekolah see all announcements within the school
        if (in_array($role, ['super-admin', 'admin-sekolah'])) {
            return $query;
        }

        if ($role === 'siswa') {
            $majorId = $user->student?->class?->major_id;

            return $query->whereIn('target_role', ['all', 'siswa'])
                ->where(function (Builder $sub) use ($majorId) {
                    $sub->whereNull('target_major_id');
                    if ($majorId) {
                        $sub->orWhere('target_major_id', $majorId);
                    }
                });
        }

        if ($role === 'guru-pembimbing') {
            $majorIds = [];
            if ($user->teacher) {
                $majorIds = $user->teacher->studentPkls()
                    ->with('student.class')
                    ->get()
                    ->pluck('student.class.major_id')
                    ->filter()
                    ->unique()
                    ->toArray();
            }

            return $query->whereIn('target_role', ['all', 'guru-pembimbing'])
                ->where(function (Builder $sub) use ($majorIds) {
                    $sub->whereNull('target_major_id');
                    if (!empty($majorIds)) {
                        $sub->orWhereIn('target_major_id', $majorIds);
                    }
                });
        }

        if ($role === 'pembimbing-dudi') {
            return $query->whereIn('target_role', ['all', 'pembimbing-dudi'])
                ->whereNull('target_major_id');
        }

        if ($role === 'kepala-sekolah') {
            return $query->whereIn('target_role', ['all', 'kepala-sekolah']);
        }

        return $query->where('target_role', 'all');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'penting' => 'Penting & Mendesak',
            'kegiatan' => 'Kegiatan PKL',
            'akademik' => 'Akademik & Kurikulum',
            'libur' => 'Hari Libur & Jadwal',
            'dudi' => 'Mitra Industri / DU-DI',
            default => 'Informasi Umum',
        };
    }

    public function getCategoryBadgeVariantAttribute(): string
    {
        return match ($this->category) {
            'penting' => 'danger',
            'kegiatan' => 'indigo',
            'akademik' => 'teal',
            'libur' => 'purple',
            'dudi' => 'warning',
            default => 'info',
        };
    }

    public function getTargetRoleLabelAttribute(): string
    {
        return match ($this->target_role) {
            'all' => 'Semua Civitas (Seluruh User)',
            'siswa' => 'Khusus Siswa PKL',
            'guru-pembimbing' => 'Guru Pembimbing',
            'pembimbing-dudi' => 'Mentor DU/DI',
            'kepala-sekolah' => 'Kepala Sekolah',
            default => ucfirst(str_replace('-', ' ', $this->target_role)),
        };
    }

    public function getTargetScopeDescriptionAttribute(): string
    {
        $rolePart = $this->target_role_label;
        $majorPart = $this->major ? "Jurusan {$this->major->name}" : 'Semua Jurusan';

        return "{$rolePart} • {$majorPart}";
    }
}
