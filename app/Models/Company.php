<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'created_by_user_id',
        'name',
        'address',
        'contact_person',
        'phone',
        'gps_enabled',
        'latitude',
        'longitude',
        'radius_meters',
        'override_hours',
        'custom_work_hours',
        'work_days',
        'custom_work_days',
        'default_start_date',
        'default_end_date',
        'requires_industry_approval',
        'is_pkl_started',
    ];

    protected $attributes = [
        'is_pkl_started' => false,
        'requires_industry_approval' => true,
    ];

    protected $casts = [
        'default_start_date' => 'date',
        'default_end_date' => 'date',
        'requires_industry_approval' => 'boolean',
        'is_pkl_started' => 'boolean',
        'gps_enabled' => 'boolean',
        'override_hours' => 'boolean',
        'custom_work_hours' => 'array',
        'custom_work_days' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function supervisors(): HasMany
    {
        return $this->hasMany(Supervisor::class);
    }

    public function studentPkls(): HasMany
    {
        return $this->hasMany(StudentPkl::class);
    }

    public function majors(): BelongsToMany
    {
        return $this->belongsToMany(Major::class, 'company_major')->withTimestamps();
    }

    public const DAY_NAMES_SHORT = [
        1 => 'Sen',
        2 => 'Sel',
        3 => 'Rab',
        4 => 'Kam',
        5 => 'Jum',
        6 => 'Sab',
        7 => 'Min',
    ];

    /**
     * Daftar hari kerja ISO-8601 (1=Senin .. 7=Minggu) yang sudah dinormalisasi untuk mode custom.
     *
     * @return array<int, int>
     */
    public function getNormalizedCustomWorkDays(): array
    {
        $days = array_map('intval', $this->custom_work_days ?? []);
        $days = array_values(array_unique(array_filter($days, fn (int $d) => $d >= 1 && $d <= 7)));
        sort($days);

        return $days;
    }

    /**
     * Check if a given date is an active work day for this company.
     * Supports:
     * - '7_days': 7 days full (Monday - Sunday / On-Project)
     * - '6_days': 6 days (Monday - Saturday)
     * - '5_days': 5 days (Monday - Friday)
     * - 'custom': specific ISO weekdays stored in custom_work_days
     */
    public function isWorkDay(mixed $date = null): bool
    {
        $dateObj = $date ? ($date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date)) : \Carbon\Carbon::today();
        $dayOfWeek = $dateObj->dayOfWeekIso; // 1 (Mon) - 7 (Sun)

        return match ($this->work_days) {
            '7_days', '7_hari' => true,
            '6_days', '6_hari' => $dayOfWeek <= 6,
            'custom' => in_array($dayOfWeek, $this->getNormalizedCustomWorkDays(), true),
            default => $dayOfWeek <= 5,
        };
    }

    /**
     * Check if this company operates through holidays (e.g. 7 days full on-project).
     */
    public function worksOnHolidays(): bool
    {
        return in_array($this->work_days, ['7_days', '7_hari'], true);
    }

    /**
     * Human-readable label for company work days schedule.
     */
    public function getWorkDaysLabelAttribute(): string
    {
        if ($this->work_days === 'custom') {
            $labels = array_map(
                fn (int $day) => self::DAY_NAMES_SHORT[$day] ?? (string) $day,
                $this->getNormalizedCustomWorkDays()
            );

            return empty($labels) ? 'Hari Kerja Custom' : 'Custom (' . implode(', ', $labels) . ')';
        }

        return match ($this->work_days) {
            '7_days', '7_hari' => '7 Hari (On-Project)',
            '6_days', '6_hari' => '6 Hari (Sen-Sab)',
            default => '5 Hari (Sen-Jum)',
        };
    }

    /**
     * Ringkasan jam kerja khusus jika override_hours aktif.
     */
    public function getCustomWorkHoursSummaryAttribute(): ?string
    {
        if (!$this->override_hours || !is_array($this->custom_work_hours)) {
            return null;
        }

        $start = $this->custom_work_hours['checkin_start'] ?? null;
        $late = $this->custom_work_hours['checkin_late'] ?? null;
        $out = $this->custom_work_hours['checkout_start'] ?? null;

        if (!$start || !$out) {
            return null;
        }

        $entryLabel = $late ? substr($late, 0, 5) : substr($start, 0, 5);
        return 'Masuk ' . substr($start, 0, 5) . ' (Batas ' . $entryLabel . ') - Pulang ' . substr($out, 0, 5);
    }

    /**
     * Aturan validasi bersama untuk jadwal hari kerja & jam kerja khusus perusahaan.
     */
    public static function scheduleValidationRules(): array
    {
        return [
            'work_days' => ['required', 'in:5_days,6_days,7_days,custom'],
            'custom_work_days' => ['required_if:work_days,custom', 'nullable', 'array', 'min:1'],
            'custom_work_days.*' => ['integer', 'between:1,7'],
            'override_hours' => ['nullable', 'boolean'],
            'checkin_start' => ['required_if:override_hours,1', 'nullable', 'date_format:H:i,H:i:s'],
            'checkin_late' => ['required_if:override_hours,1', 'nullable', 'date_format:H:i,H:i:s'],
            'checkin_alfa' => ['required_if:override_hours,1', 'nullable', 'date_format:H:i,H:i:s'],
            'checkout_start' => ['required_if:override_hours,1', 'nullable', 'date_format:H:i,H:i:s'],
        ];
    }

    /**
     * Normalisasi payload jadwal kerja dari request Admin Sekolah maupun Kajur.
     */
    public static function extractSchedulePayload(\Illuminate\Http\Request $request): array
    {
        $workDays = $request->input('work_days', '5_days');
        $customWorkDays = null;

        if ($workDays === 'custom') {
            $days = array_map('intval', (array) $request->input('custom_work_days', []));
            $days = array_values(array_unique(array_filter($days, fn (int $d) => $d >= 1 && $d <= 7)));
            sort($days);
            $customWorkDays = $days;
        }

        $overrideHours = $request->boolean('override_hours');
        $customWorkHours = null;

        if ($overrideHours) {
            $normalizeTime = function (?string $value, string $fallback): string {
                $raw = trim((string) $value);
                if ($raw === '') {
                    return $fallback;
                }
                return strlen($raw) === 5 ? $raw . ':00' : $raw;
            };

            $customWorkHours = [
                'checkin_start' => $normalizeTime($request->input('checkin_start'), '06:00:00'),
                'checkin_late' => $normalizeTime($request->input('checkin_late'), '08:00:00'),
                'checkin_alfa' => $normalizeTime($request->input('checkin_alfa'), '11:00:00'),
                'checkout_start' => $normalizeTime($request->input('checkout_start'), '16:00:00'),
            ];
        }

        return [
            'work_days' => $workDays,
            'custom_work_days' => $customWorkDays,
            'override_hours' => $overrideHours,
            'custom_work_hours' => $customWorkHours,
        ];
    }
}
