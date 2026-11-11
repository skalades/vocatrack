<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\StudentPkl;
use App\Services\HolidayService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EvaluateDailyAbsence extends Command
{
    protected $signature = 'vokatrack:evaluate-absence';
    protected $description = 'Evaluasi otomatis ketidakhadiran siswa PKL (alfa) pada akhir jam kerja';

    public function handle(HolidayService $holidayService): int
    {
        $today = Carbon::today();
        $todayString = $today->toDateString();
        $dayOfWeek = $today->dayOfWeekIso; // 1 (Mon) to 7 (Sun)

        $this->info("Memulai evaluasi kehadiran untuk tanggal: {$todayString}");

        $alfaCount = 0;
        $holidaySchoolCache = []; // Cache school holiday status: [school_id => bool]

        // Scalable chunking to avoid OOM and batch lookups to prevent N+1 queries
        StudentPkl::with(['company', 'school'])
            ->where('status', 'active')
            ->chunkById(100, function ($activeStudentPkls) use (&$alfaCount, &$holidaySchoolCache, $holidayService, $today, $todayString, $dayOfWeek) {
                $spklIds = $activeStudentPkls->pluck('id');

                // Batch fetch today's attendance and active requests for the whole chunk
                $existingAttendanceIds = Attendance::whereIn('student_pkl_id', $spklIds)
                    ->where('date', $todayString)
                    ->pluck('student_pkl_id')
                    ->flip();

                $approvedRequests = AttendanceRequest::whereIn('student_pkl_id', $spklIds)
                    ->where('status', 'approved')
                    ->whereDate('start_date', '<=', $todayString)
                    ->whereDate('end_date', '>=', $todayString)
                    ->get()
                    ->keyBy('student_pkl_id');

                foreach ($activeStudentPkls as $spkl) {
                    $company = $spkl->company;
                    if (!$company) {
                        continue;
                    }

                    // 1. Check school holiday status (memoized O(1) lookup per school)
                    if (!array_key_exists($spkl->school_id, $holidaySchoolCache)) {
                        $holidaySchoolCache[$spkl->school_id] = $holidayService->isHoliday($spkl->school_id, $today);
                    }

                    if ($holidaySchoolCache[$spkl->school_id]) {
                        // Hari ini libur untuk sekolah ini, skip evaluasi alfa
                        continue;
                    }

                    // 2. Check work days: 5_days (Mon-Fri) or 6_days (Mon-Sat)
                    $isWorkDay = ($company->work_days === '6_days') ? ($dayOfWeek <= 6) : ($dayOfWeek <= 5);

                    if (!$isWorkDay) {
                        continue;
                    }

                    // Check if attendance already logged today (O(1) memory lookup)
                    if (isset($existingAttendanceIds[$spkl->id])) {
                        continue;
                    }

                    // Check if there is an approved attendance request for today (O(1) memory lookup)
                    $hasApprovedRequest = $approvedRequests->get($spkl->id);

                    if ($hasApprovedRequest) {
                        // Record mapped attendance based on request
                        Attendance::create([
                            'school_id' => $spkl->school_id,
                            'student_pkl_id' => $spkl->id,
                            'date' => $todayString,
                            'status' => $hasApprovedRequest->type,
                            'checkout_notes' => 'Otomatis dari pengajuan: ' . $hasApprovedRequest->type,
                        ]);
                        continue;
                    }

                    // Record as ALFA
                    Attendance::create([
                        'school_id' => $spkl->school_id,
                        'student_pkl_id' => $spkl->id,
                        'date' => $todayString,
                        'status' => 'alfa',
                    ]);

                    $alfaCount++;
                }
            });

        $this->info("Evaluasi selesai. Total siswa ditandai alfa: {$alfaCount}");

        return Command::SUCCESS;
    }
}
