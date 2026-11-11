<?php

namespace App\Services;

use App\Models\Company;
use App\Models\StudentPkl;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Notifications\RequestStatusUpdatedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function recordCheckIn(StudentPkl $spkl, array $data, ?GeolocationGuardService $geoGuard = null): Attendance
    {
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $company = $spkl->company;
        $geoGuard = $geoGuard ?? app(GeolocationGuardService::class);

        // 1. Evaluate GPS, accuracy & anti-fake GPS guard
        $geoEval = $geoGuard->evaluateCheckinLocation($company, $data, request()?->userAgent());
        $isWithinRadius = $geoEval['is_within_radius'];
        $accuracyMeters = $geoEval['accuracy_meters'];
        $isMockDetected = $geoEval['is_mock_detected'];

        // 2. Resolve Work Hours & Check Late Status
        $workHours = $this->resolveWorkHours($company, $spkl->school_id);

        $checkinStart = Carbon::createFromTimeString($workHours['checkin_start']);
        if ($now->lessThan($checkinStart)) {
            throw ValidationException::withMessages(['time' => 'Tombol presensi baru dapat ditekan mulai pukul ' . substr($workHours['checkin_start'], 0, 5) . ' WIB.']);
        }

        $lateLimit = Carbon::createFromTimeString($workHours['checkin_late']);
        $status = $now->lessThanOrEqualTo($lateLimit) ? 'hadir' : 'terlambat';

        $checkoutNotes = null;
        if (!$isWithinRadius && !empty($data['reason'])) {
            $checkoutNotes = 'Alasan Luar Radius: ' . $data['reason'];
        }
        if ($isMockDetected) {
            $checkoutNotes = ($checkoutNotes ? $checkoutNotes . ' | ' : '') . '[FLAG] Indikasi Fake GPS / Mock Location Terdeteksi';
        }

        // 3. Atomic check and insert with transaction & duplicate constraint protection
        return DB::transaction(function () use ($spkl, $today, $status, $now, $data, $isWithinRadius, $accuracyMeters, $isMockDetected, $checkoutNotes) {
            $existing = Attendance::where('student_pkl_id', $spkl->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw ValidationException::withMessages(['attendance' => 'Presensi hari ini sudah tercatat.']);
            }

            try {
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
                    'accuracy_meters' => $accuracyMeters,
                    'is_mock_detected' => $isMockDetected,
                    'checkout_notes' => $checkoutNotes,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Integrity constraint violation')) {
                    throw ValidationException::withMessages(['attendance' => 'Presensi hari ini sudah tercatat.']);
                }
                throw $e;
            }
        });
    }

    public function recordCheckOut(StudentPkl $spkl, array $data): Attendance
    {
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // Atomic check-out with pessimistic lock
        return DB::transaction(function () use ($spkl, $today, $now, $data) {
            $attendance = Attendance::where('student_pkl_id', $spkl->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
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
        });
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

    /**
     * Process single attendance request with locking and attendance record creation.
     */
    public function processRequest(AttendanceRequest $attRequest, string $action, ?string $notes, User $user): AttendanceRequest
    {
        if (!in_array($action, ['approve', 'reject'])) {
            throw ValidationException::withMessages(['action' => 'Aksi permohonan tidak valid.']);
        }

        $isGuru = $user->hasRole('guru-pembimbing');
        $isDudi = $user->hasRole('pembimbing-dudi');

        if (!$isGuru && !$isDudi) {
            throw ValidationException::withMessages(['role' => 'Pengguna tidak memiliki wewenang memproses permohonan.']);
        }

        $reviewerRole = $isGuru ? 'guru-pembimbing' : 'pembimbing-dudi';
        $status = ($action === 'approve') ? 'approved' : 'rejected';

        return DB::transaction(function () use ($attRequest, $action, $notes, $user, $reviewerRole, $status, $isGuru) {
            $locked = AttendanceRequest::where('id', $attRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Permohonan presensi ini sudah diproses sebelumnya (Status: ' . $locked->status . ').'
                ]);
            }

            $locked->update([
                'status' => $status,
                'reviewer_id' => $user->id,
                'reviewer_role' => $reviewerRole,
                'reviewer_notes' => $notes,
                'reviewed_at' => now(),
            ]);

            if ($status === 'approved') {
                $currentDate = Carbon::parse($locked->start_date);
                $endDate = Carbon::parse($locked->end_date);
                $notePrefix = $isGuru ? 'Pengajuan disetujui Guru: ' : 'Disetujui Mentor DU/DI: ';

                while ($currentDate->lessThanOrEqualTo($endDate)) {
                    $attendanceData = [
                        'school_id' => $locked->school_id,
                        'status' => $locked->type,
                        'checkout_notes' => $notePrefix . $locked->reason,
                    ];
                    if (!$isGuru) {
                        $attendanceData['is_within_radius'] = true;
                    }

                    Attendance::updateOrCreate(
                        [
                            'student_pkl_id' => $locked->student_pkl_id,
                            'date' => $currentDate->toDateString(),
                        ],
                        $attendanceData
                    );
                    $currentDate->addDay();
                }
            }

            // Notify student
            $studentUser = $locked->studentPkl?->student?->user;
            if ($studentUser) {
                $studentUser->notify(new RequestStatusUpdatedNotification($locked, $status, $notes));
            }

            return $locked;
        });
    }

    /**
     * Bulk process attendance requests with pessimistic lock and batch attendance updates.
     */
    public function bulkProcessRequests(array $requestIds, string $action, ?string $notes, User $user): array
    {
        if (!in_array($action, ['approve', 'reject'])) {
            throw ValidationException::withMessages(['action' => 'Aksi permohonan tidak valid.']);
        }

        $isGuru = $user->hasRole('guru-pembimbing');
        $isDudi = $user->hasRole('pembimbing-dudi');

        if (!$isGuru && !$isDudi) {
            throw ValidationException::withMessages(['role' => 'Pengguna tidak memiliki wewenang memproses permohonan.']);
        }

        $reviewerRole = $isGuru ? 'guru-pembimbing' : 'pembimbing-dudi';
        $status = ($action === 'approve') ? 'approved' : 'rejected';

        $processed = 0;
        $skipped = 0;

        DB::transaction(function () use ($requestIds, $action, $notes, $user, $reviewerRole, $status, $isGuru, $isDudi, &$processed, &$skipped) {
            $requests = AttendanceRequest::whereIn('id', $requestIds)
                ->with('studentPkl')
                ->lockForUpdate()
                ->get();

            foreach ($requests as $locked) {
                // Verify ownership / tenant assignment
                if ($isGuru) {
                    $teacher = $user->teacher;
                    if (!$teacher || $locked->studentPkl->teacher_id !== $teacher->id) {
                        $skipped++;
                        continue;
                    }
                } elseif ($isDudi) {
                    $supervisor = $user->supervisor;
                    if (!$supervisor || $locked->studentPkl->company_id !== $supervisor->company_id) {
                        $skipped++;
                        continue;
                    }
                }

                if ($locked->status !== 'pending') {
                    $skipped++;
                    continue;
                }

                $locked->update([
                    'status' => $status,
                    'reviewer_id' => $user->id,
                    'reviewer_role' => $reviewerRole,
                    'reviewer_notes' => $notes,
                    'reviewed_at' => now(),
                ]);

                if ($status === 'approved') {
                    $currentDate = Carbon::parse($locked->start_date);
                    $endDate = Carbon::parse($locked->end_date);
                    $notePrefix = $isGuru ? 'Pengajuan disetujui Guru: ' : 'Disetujui Mentor DU/DI: ';

                    while ($currentDate->lessThanOrEqualTo($endDate)) {
                        $attendanceData = [
                            'school_id' => $locked->school_id,
                            'status' => $locked->type,
                            'checkout_notes' => $notePrefix . $locked->reason,
                        ];
                        if (!$isGuru) {
                            $attendanceData['is_within_radius'] = true;
                        }

                        Attendance::updateOrCreate(
                            [
                                'student_pkl_id' => $locked->student_pkl_id,
                                'date' => $currentDate->toDateString(),
                            ],
                            $attendanceData
                        );
                        $currentDate->addDay();
                    }
                }

                // Notify student
                $studentUser = $locked->studentPkl?->student?->user;
                if ($studentUser) {
                    $studentUser->notify(new RequestStatusUpdatedNotification($locked, $status, $notes));
                }

                $processed++;
            }
        });

        return [
            'processed' => $processed,
            'skipped' => $skipped,
        ];
    }
}
