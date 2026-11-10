<?php

namespace App\Services;

use App\Models\Company;
use App\Models\StudentPkl;
use App\Models\Attendance;
use App\Models\SchoolSetting;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function recordCheckIn(StudentPkl $spkl, array $data): Attendance
    {
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // 1. Check duplicate check-in today
        if (Attendance::where('student_pkl_id', $spkl->id)->whereDate('date', $today)->exists()) {
            throw ValidationException::withMessages(['attendance' => 'Presensi hari ini sudah tercatat.']);
        }

        $company = $spkl->company;
        $isWithinRadius = true;

        // 2. Evaluate GPS if enabled by DU/DI
        if ($company->gps_enabled) {
            if (!isset($data['latitude']) || !isset($data['longitude'])) {
                throw ValidationException::withMessages(['location' => 'Data koordinat GPS perangkat diperlukan untuk presensi di DU/DI ini.']);
            }

            $distance = $this->calculateHaversineDistance(
                (float) $data['latitude'],
                (float) $data['longitude'],
                (float) $company->latitude,
                (float) $company->longitude
            );

            $isWithinRadius = ($distance <= (float) $company->radius_meters);
        }

        // 3. Resolve Work Hours & Check Late Status
        $workHours = $this->resolveWorkHours($company, $spkl->school_id);

        $checkinStart = Carbon::createFromTimeString($workHours['checkin_start']);
        if ($now->lessThan($checkinStart)) {
            throw ValidationException::withMessages(['time' => 'Tombol presensi baru dapat ditekan mulai pukul ' . substr($workHours['checkin_start'], 0, 5) . ' WIB.']);
        }

        $lateLimit = Carbon::createFromTimeString($workHours['checkin_late']);
        $status = $now->lessThanOrEqualTo($lateLimit) ? 'hadir' : 'terlambat';

        return Attendance::create([
            'school_id' => $spkl->school_id,
            'student_pkl_id' => $spkl->id,
            'date' => $today,
            'status' => $status,
            'checkin_time' => $now->toTimeString(),
            'checkin_photo_path' => $data['photo_path'] ?? null,
            'checkin_lat' => $data['latitude'] ?? null,
            'checkin_lng' => $data['longitude'] ?? null,
            'is_within_radius' => $isWithinRadius,
        ]);
    }

    public function recordCheckOut(StudentPkl $spkl, array $data): Attendance
    {
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $attendance = Attendance::where('student_pkl_id', $spkl->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            throw ValidationException::withMessages(['checkout' => 'Anda belum melakukan check-in hari ini.']);
        }

        if ($attendance->checkout_time) {
            throw ValidationException::withMessages(['checkout' => 'Check-out untuk hari ini sudah selesai dilakukan.']);
        }

        $workHours = $this->resolveWorkHours($spkl->company, $spkl->school_id);
        $checkoutStart = Carbon::createFromTimeString($workHours['checkout_start']);

        if ($now->lessThan($checkoutStart)) {
            throw ValidationException::withMessages(['checkout' => 'Check-out baru dapat dilakukan mulai pukul ' . substr($workHours['checkout_start'], 0, 5) . ' WIB.']);
        }

        $attendance->update([
            'checkout_time' => $now->toTimeString(),
            'checkout_notes' => $data['checkout_notes'] ?? null,
        ]);

        return $attendance;
    }

    public function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * (sin($dLon / 2) ** 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    public function resolveWorkHours(Company $company, int $schoolId): array
    {
        if ($company->override_hours && !empty($company->custom_work_hours)) {
            $hours = is_string($company->custom_work_hours) 
                ? json_decode($company->custom_work_hours, true) 
                : $company->custom_work_hours;

            if (is_array($hours) && isset($hours['checkin_start'], $hours['checkin_late'], $hours['checkout_start'])) {
                return $hours;
            }
        }

        // Get school custom settings or fallback to defaults
        $settings = SchoolSetting::where('school_id', $schoolId)->pluck('value', 'key');

        return [
            'checkin_start' => $settings['checkin_start'] ?? '06:00:00',
            'checkin_late'  => $settings['checkin_late'] ?? '08:00:00',
            'checkin_alfa'  => $settings['checkin_alfa'] ?? '11:00:00',
            'checkout_start'=> $settings['checkout_start'] ?? '16:00:00',
        ];
    }
}
