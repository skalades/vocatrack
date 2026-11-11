<?php

namespace App\Services;

use App\Models\SchoolHoliday;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HolidayService
{
    /**
     * Check if a given date is a registered holiday for the given school.
     */
    public function isHoliday(int $schoolId, Carbon|string $date): bool
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return SchoolHoliday::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereDate('holiday_date', $dateString)
            ->exists();
    }

    /**
     * Get holidays for a school, optionally filtered by year and month.
     */
    public function getHolidays(int $schoolId, ?int $year = null, ?int $month = null): Collection
    {
        $query = SchoolHoliday::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->orderBy('holiday_date', 'asc');

        if ($year) {
            $query->whereYear('holiday_date', $year);
        }

        if ($month) {
            $query->whereMonth('holiday_date', $month);
        }

        return $query->get();
    }

    /**
     * Create a new custom or national holiday for a school.
     */
    public function createHoliday(int $schoolId, array $data): SchoolHoliday
    {
        return SchoolHoliday::create([
            'school_id' => $schoolId,
            'title' => $data['title'],
            'holiday_date' => $data['holiday_date'],
            'description' => $data['description'] ?? null,
            'is_national' => !empty($data['is_national']),
        ]);
    }

    /**
     * Delete a holiday.
     */
    public function deleteHoliday(SchoolHoliday $holiday): bool
    {
        return (bool) $holiday->delete();
    }

    /**
     * Sync standard Indonesian national holidays for the specified year.
     * Idempotent (uses updateOrCreate).
     */
    public function syncNationalHolidays(int $schoolId, int $year): int
    {
        $nationalHolidays = $this->getDefaultNationalHolidays($year);
        $count = 0;

        foreach ($nationalHolidays as $item) {
            SchoolHoliday::withoutGlobalScopes()->updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'holiday_date' => $item['date'],
                ],
                [
                    'title' => $item['title'],
                    'description' => $item['description'] ?? 'Hari Libur Nasional Republik Indonesia',
                    'is_national' => true,
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Pre-defined list of official Indonesian national holidays and cuti bersama by year.
     */
    protected function getDefaultNationalHolidays(int $year): array
    {
        // Standard fixed and estimated Indonesian national holidays
        $fixed = [
            ['date' => "{$year}-01-01", 'title' => 'Tahun Baru Masehi'],
            ['date' => "{$year}-05-01", 'title' => 'Hari Buruh Internasional'],
            ['date' => "{$year}-06-01", 'title' => 'Hari Lahir Pancasila'],
            ['date' => "{$year}-08-17", 'title' => 'Hari Kemerdekaan Republik Indonesia'],
            ['date' => "{$year}-12-25", 'title' => 'Hari Raya Natal'],
        ];

        // Specific calendar references for 2024 - 2027
        $lunarAndIslamic = [
            2024 => [
                ['date' => '2024-02-08', 'title' => 'Isra Mi\'raj Nabi Muhammad SAW'],
                ['date' => '2024-02-10', 'title' => 'Tahun Baru Imlek 2575 Kongzili'],
                ['date' => '2024-03-11', 'title' => 'Hari Suci Nyepi Tahun Baru Saka 1946'],
                ['date' => '2024-03-29', 'title' => 'Wafat Isa Al Masih'],
                ['date' => '2024-04-10', 'title' => 'Hari Raya Idul Fitri 1445 H'],
                ['date' => '2024-04-11', 'title' => 'Hari Raya Idul Fitri 1445 H'],
                ['date' => '2024-05-09', 'title' => 'Kenaikan Isa Al Masih'],
                ['date' => '2024-05-23', 'title' => 'Hari Raya Waisak 2568 BE'],
                ['date' => '2024-06-17', 'title' => 'Hari Raya Idul Adha 1445 H'],
                ['date' => '2024-07-07', 'title' => 'Tahun Baru Islam 1446 H'],
                ['date' => '2024-09-16', 'title' => 'Maulid Nabi Muhammad SAW'],
            ],
            2025 => [
                ['date' => '2025-01-27', 'title' => 'Isra Mi\'raj Nabi Muhammad SAW'],
                ['date' => '2025-01-29', 'title' => 'Tahun Baru Imlek 2576 Kongzili'],
                ['date' => '2025-03-29', 'title' => 'Hari Suci Nyepi Tahun Baru Saka 1947'],
                ['date' => '2025-03-31', 'title' => 'Hari Raya Idul Fitri 1446 H'],
                ['date' => '2025-04-01', 'title' => 'Hari Raya Idul Fitri 1446 H'],
                ['date' => '2025-04-18', 'title' => 'Wafat Yesus Kristus'],
                ['date' => '2025-05-12', 'title' => 'Hari Raya Waisak 2569 BE'],
                ['date' => '2025-05-29', 'title' => 'Kenaikan Yesus Kristus'],
                ['date' => '2025-06-06', 'title' => 'Hari Raya Idul Adha 1446 H'],
                ['date' => '2025-06-27', 'title' => 'Tahun Baru Islam 1447 H'],
                ['date' => '2025-09-05', 'title' => 'Maulid Nabi Muhammad SAW'],
            ],
            2026 => [
                ['date' => '2026-01-16', 'title' => 'Isra Mi\'raj Nabi Muhammad SAW'],
                ['date' => '2026-02-17', 'title' => 'Tahun Baru Imlek 2577 Kongzili'],
                ['date' => '2026-03-19', 'title' => 'Hari Suci Nyepi Tahun Baru Saka 1948'],
                ['date' => '2026-03-20', 'title' => 'Hari Raya Idul Fitri 1447 H'],
                ['date' => '2026-03-21', 'title' => 'Hari Raya Idul Fitri 1447 H'],
                ['date' => '2026-04-03', 'title' => 'Wafat Yesus Kristus'],
                ['date' => '2026-05-14', 'title' => 'Kenaikan Yesus Kristus'],
                ['date' => '2026-05-31', 'title' => 'Hari Raya Waisak 2570 BE'],
                ['date' => '2026-05-27', 'title' => 'Hari Raya Idul Adha 1447 H'],
                ['date' => '2026-06-16', 'title' => 'Tahun Baru Islam 1448 H'],
                ['date' => '2026-08-25', 'title' => 'Maulid Nabi Muhammad SAW'],
            ],
            2027 => [
                ['date' => '2027-01-05', 'title' => 'Isra Mi\'raj Nabi Muhammad SAW'],
                ['date' => '2027-02-06', 'title' => 'Tahun Baru Imlek 2578 Kongzili'],
                ['date' => '2027-03-09', 'title' => 'Hari Suci Nyepi Tahun Baru Saka 1949'],
                ['date' => '2027-03-10', 'title' => 'Hari Raya Idul Fitri 1448 H'],
                ['date' => '2027-03-11', 'title' => 'Hari Raya Idul Fitri 1448 H'],
                ['date' => '2027-03-26', 'title' => 'Wafat Yesus Kristus'],
                ['date' => '2027-05-06', 'title' => 'Kenaikan Yesus Kristus'],
                ['date' => '2027-05-20', 'title' => 'Hari Raya Waisak 2571 BE'],
                ['date' => '2027-05-16', 'title' => 'Hari Raya Idul Adha 1448 H'],
                ['date' => '2027-06-06', 'title' => 'Tahun Baru Islam 1449 H'],
                ['date' => '2027-08-15', 'title' => 'Maulid Nabi Muhammad SAW'],
            ],
        ];

        $dynamic = $lunarAndIslamic[$year] ?? [];

        return array_merge($fixed, $dynamic);
    }
}
