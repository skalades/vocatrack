<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\StudentPkl;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EvaluateDailyAbsence extends Command
{
    protected $signature = 'vokatrack:evaluate-absence';
    protected $description = 'Evaluasi otomatis ketidakhadiran siswa PKL (alfa) pada akhir jam kerja';

    public function handle(): int
    {
        $today = Carbon::today();
        $todayString = $today->toDateString();
        $dayOfWeek = $today->dayOfWeekIso; // 1 (Mon) to 7 (Sun)

        $this->info("Memulai evaluasi kehadiran untuk tanggal: {$todayString}");

        $alfaCount = 0;

        // Scalable chunking to avoid OOM in high-volume production deployments
        StudentPkl::with(['company', 'school'])
            ->where('status', 'active')
            ->chunkById(100, function ($activeStudentPkls) use (&$alfaCount, $todayString, $dayOfWeek) {
                foreach ($activeStudentPkls as $spkl) {
                    $company = $spkl->company;
                    if (!$company) {
                        continue;
                    }

                    // Check work days: 5_days (Mon-Fri) or 6_days (Mon-Sat)
                    $isWorkDay = ($company->work_days === '6_days') ? ($dayOfWeek <= 6) : ($dayOfWeek <= 5);

                    if (!$isWorkDay) {
                        continue;
                    }

                    // Check if attendance already logged today
                    $hasAttendance = Attendance::where('student_pkl_id', $spkl->id)
                        ->where('date', $todayString)
                        ->exists();

                    if ($hasAttendance) {
                        continue;
                    }

                    // Check if there is an approved attendance request for today
                    $hasApprovedRequest = AttendanceRequest::where('student_pkl_id', $spkl->id)
                        ->where('status', 'approved')
                        ->whereDate('start_date', '<=', $todayString)
                        ->whereDate('end_date', '>=', $todayString)
                        ->first();

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
