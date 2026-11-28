<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supervisor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'position',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function studentPkls(): HasMany
    {
        return $this->hasMany(StudentPkl::class);
    }

    /**
     * Kumpulkan seluruh ID jurusan yang terhubung dengan pembimbing DU/DI ini
     * (jurusan mitra perusahaan maupun siswa magang yang ditempatkan).
     */
    public function accessibleMajorIds(): array
    {
        $company = $this->company;
        if (! $company) {
            return [];
        }

        $companyMajorIds = $company->majors()->pluck('majors.id');
        $internMajorIds = $company->studentPkls()
            ->with('student.class')
            ->get()
            ->pluck('student.class.major_id');

        return $companyMajorIds
            ->merge($internMajorIds)
            ->filter()
            ->unique()
            ->values()
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
