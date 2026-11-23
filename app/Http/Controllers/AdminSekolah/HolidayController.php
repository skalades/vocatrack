<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Http\Requests\AdminSekolah\StoreHolidayRequest;
use App\Models\SchoolHoliday;
use App\Services\HolidayService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HolidayController extends BaseAdminSekolahController
{
    public function holidays(Request $request, HolidayService $holidayService)
    {
        $schoolId = $this->getSchoolId();
        $selectedYear = (int) $request->input('year', Carbon::now()->year);
        $selectedMonth = $request->filled('month') ? (int) $request->month : null;

        $holidays = $holidayService->getHolidays($schoolId, $selectedYear, $selectedMonth);

        $stats = [
            'total' => SchoolHoliday::withoutGlobalScopes()->where('school_id', $schoolId)->whereYear('holiday_date', $selectedYear)->count(),
            'national' => SchoolHoliday::withoutGlobalScopes()->where('school_id', $schoolId)->whereYear('holiday_date', $selectedYear)->where('is_national', true)->count(),
            'custom' => SchoolHoliday::withoutGlobalScopes()->where('school_id', $schoolId)->whereYear('holiday_date', $selectedYear)->where('is_national', false)->count(),
        ];

        return view('adminsekolah.holidays', compact('holidays', 'selectedYear', 'selectedMonth', 'stats'));
    }

    public function storeHoliday(StoreHolidayRequest $request, HolidayService $holidayService)
    {
        $schoolId = $this->getSchoolId();

        $holidayService->createHoliday($schoolId, $request->validated());

        return back()->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function destroyHoliday(SchoolHoliday $holiday, HolidayService $holidayService)
    {
        $schoolId = $this->getSchoolId();
        abort_if($holiday->school_id !== $schoolId, 403);

        $holidayService->deleteHoliday($holiday);

        return back()->with('success', 'Hari libur berhasil dihapus.');
    }

    public function syncNationalHolidays(Request $request, HolidayService $holidayService)
    {
        $schoolId = $this->getSchoolId();
        $year = (int) $request->input('year', Carbon::now()->year);

        $count = $holidayService->syncNationalHolidays($schoolId, $year);

        return back()->with('success', "Berhasil mensinkronkan {$count} hari libur nasional untuk tahun {$year}.");
    }
}
