<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentPkl extends Model
{
    use HasFactory, BelongsToSchool;

    protected $table = 'student_pkl';

    protected $fillable = [
        'school_id',
        'student_id',
        'company_id',
        'pkl_period_id',
        'teacher_id',
        'supervisor_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function getEffectiveStartDateAttribute(): ?\Carbon\Carbon
    {
        if ($this->start_date) {
            return \Carbon\Carbon::parse($this->start_date);
        }

        if ($this->company?->default_start_date) {
            return \Carbon\Carbon::parse($this->company->default_start_date);
        }

        return $this->pklPeriod?->start_date;
    }

    public function getEffectiveEndDateAttribute(): ?\Carbon\Carbon
    {
        if ($this->end_date) {
            return \Carbon\Carbon::parse($this->end_date);
        }

        if ($this->company?->default_end_date) {
            return \Carbon\Carbon::parse($this->company->default_end_date);
        }

        return $this->pklPeriod?->end_date;
    }

    /**
     * Cek apakah pelaksanaan PKL saat ini sudah dimulai oleh pihak perusahaan (is_pkl_started).
     */
    public function getIsStartedAttribute(): bool
    {
        return (bool) ($this->company?->is_pkl_started ?? false);
    }

    /**
     * Memeriksa apakah siswa ini merupakan siswa simulasi/uji coba
     * (Mode uji coba aktif pada sekolah DAN DU/DI belum mulai PKL).
     */
    public function isTestStudent(): bool
    {
        return (bool) $this->school?->isTestModeActive()
            && (!$this->company || !$this->company->is_pkl_started);
    }

    /**
     * Status terhitung penugasan siswa (mempertimbangkan pengaturan mulai dari perusahaan mitra).
     */
    public function getComputedStatusAttribute(): string
    {
        if (in_array($this->status, ['completed', 'withdrawn'], true)) {
            return $this->status;
        }

        return $this->is_started ? 'active' : 'pending_start';
    }

    /**
     * Label teks status penugasan siswa untuk tampilan UI.
     */
    public function getComputedStatusLabelAttribute(): string
    {
        return match ($this->computed_status) {
            'completed' => 'SELESAI',
            'withdrawn' => 'DIBATALKAN',
            'active' => 'AKTIF',
            default => 'BELUM MULAI',
        };
    }

    public function coversDate(?\Carbon\Carbon $date = null): bool
    {
        $target = ($date ?? \Carbon\Carbon::today())->copy()->startOfDay();
        $start = $this->effective_start_date?->copy()->startOfDay();
        $end = $this->effective_end_date?->copy()->startOfDay();

        if ($start && $target->lt($start)) {
            return false;
        }

        if ($end && $target->gt($end)) {
            return false;
        }

        return true;
    }

    public function isPklActiveToday(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->coversDate(\Carbon\Carbon::today());
    }

    /**
     * Cek apakah rentang tanggal penempatan baru bertabrakan dengan penempatan lain
     * milik siswa yang sama dalam periode PKL yang sama.
     */
    public static function findOverlappingPlacement(
        int $studentId,
        int $periodId,
        ?string $startDate,
        ?string $endDate,
        ?int $ignoreId = null
    ): ?self {
        $newStart = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : null;
        $newEnd = $endDate ? \Carbon\Carbon::parse($endDate)->startOfDay() : null;

        $existingPlacements = self::with(['company', 'pklPeriod'])
            ->where('student_id', $studentId)
            ->where('pkl_period_id', $periodId)
            ->whereNotIn('status', ['withdrawn', 'canceled'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->get();

        foreach ($existingPlacements as $existing) {
            $exStart = $existing->effective_start_date?->copy()->startOfDay();
            $exEnd = $existing->effective_end_date?->copy()->startOfDay();

            $startsBeforeExistingEnds = !$newStart || !$exEnd || $newStart->lte($exEnd);
            $endsAfterExistingStarts = !$newEnd || !$exStart || $newEnd->gte($exStart);

            if ($startsBeforeExistingEnds && $endsAfterExistingStarts) {
                return $existing;
            }
        }

        return null;
    }

    /**
     * Menentukan 1 penempatan PKL yang menjadi target penilaian & e-sertifikat
     * dalam satu periode (aturan: penempatan terakhir berdasarkan tanggal selesai/mulai).
     */
    public static function resolveAssessmentTargetForPeriod(int $studentId, int $periodId): ?self
    {
        $placements = self::with(['pklPeriod', 'assessment', 'company'])
            ->where('student_id', $studentId)
            ->where('pkl_period_id', $periodId)
            ->whereNotIn('status', ['withdrawn', 'canceled'])
            ->get();

        if ($placements->isEmpty()) {
            return null;
        }

        // Jika sudah ada nilai pada salah satu penempatan di periode ini, pertahankan penempatan tersebut
        $alreadyGraded = $placements->first(fn (self $spkl) => $spkl->assessment !== null);
        if ($alreadyGraded) {
            return $alreadyGraded;
        }

        return $placements
            ->sortByDesc(function (self $spkl) {
                $endTs = $spkl->effective_end_date?->timestamp ?? 0;
                $startTs = $spkl->effective_start_date?->timestamp ?? 0;
                return [$endTs, $startTs, $spkl->id];
            })
            ->first();
    }

    public function isAssessmentTarget(): bool
    {
        if (in_array($this->status, ['withdrawn', 'canceled'], true)) {
            return false;
        }

        $target = self::resolveAssessmentTargetForPeriod($this->student_id, $this->pkl_period_id);
        return $target !== null && $target->id === $this->id;
    }

    /**
     * Scope query untuk hanya menampilkan 1 penempatan target penilaian per (student, period).
     */
    public function scopeOnlyAssessmentTargets(\Illuminate\Database\Eloquent\Builder $query, ?int $schoolId = null): \Illuminate\Database\Eloquent\Builder
    {
        $multiPairs = self::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNotIn('status', ['withdrawn', 'canceled'])
            ->select('student_id', 'pkl_period_id')
            ->groupBy('student_id', 'pkl_period_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $excludedIds = [];
        foreach ($multiPairs as $pair) {
            if (!$pair->pkl_period_id) {
                continue;
            }
            $target = self::resolveAssessmentTargetForPeriod((int) $pair->student_id, (int) $pair->pkl_period_id);
            if ($target) {
                $others = self::where('student_id', $pair->student_id)
                    ->where('pkl_period_id', $pair->pkl_period_id)
                    ->where('id', '!=', $target->id)
                    ->pluck('id')
                    ->all();
                array_push($excludedIds, ...$others);
            }
        }

        return $query->whereNotIn('student_pkl.status', ['withdrawn', 'canceled'])
            ->when(!empty($excludedIds), fn ($q) => $q->whereNotIn('student_pkl.id', $excludedIds));
    }

    /**
     * Resolusi tanggal mulai & selesai penempatan PKL secara konsisten untuk Admin & Kajur.
     *
     * @return array{start_date: ?string, end_date: ?string}
     */
    public static function resolveDefaultPlacementDates(
        ?Student $student,
        ?Company $company,
        ?PklPeriod $period,
        ?string $requestedStartDate = null,
        ?string $requestedEndDate = null
    ): array {
        $major = $student?->class?->major;

        $resolvedStartDate = $requestedStartDate ?: ($company?->default_start_date?->toDateString() ?? $period?->start_date?->toDateString());
        $resolvedEndDate = $requestedEndDate ?: ($company?->default_end_date?->toDateString() ?? null);

        if (!$resolvedEndDate && $resolvedStartDate && $major?->default_pkl_duration_months) {
            $resolvedEndDate = \Carbon\Carbon::parse($resolvedStartDate)
                ->addMonths($major->default_pkl_duration_months)
                ->toDateString();
        }

        $resolvedEndDate = $resolvedEndDate ?? $period?->end_date?->toDateString();

        return [
            'start_date' => $resolvedStartDate,
            'end_date' => $resolvedEndDate,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pklPeriod(): BelongsTo
    {
        return $this->belongsTo(PklPeriod::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceRequests(): HasMany
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(Assessment::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    public function supervisionAssignmentItems(): HasMany
    {
        return $this->hasMany(SupervisionAssignmentStudent::class);
    }

    public function supervisionVisits(): BelongsToMany
    {
        return $this->belongsToMany(SupervisionVisit::class, 'supervision_visit_students')
            ->withTimestamps();
    }
}
