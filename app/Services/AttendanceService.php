<?php

namespace App\Services;

use App\Models\Company;
use App\Models\StudentPkl;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\SchoolSetting;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\RequestStatusUpdatedNotification;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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

        // Periksa apakah fitur geofencing tenant aktif untuk presensi masuk
        $school = $spkl->school;
        $isTestStudent = $spkl->isTestStudent();
        $isSchoolGeofence = $school ? $school->isFeatureEnabled('feature_checkin_geofence', true) : true;
        if ($isTestStudent) {
            $isSchoolGeofence = false;
        }
        $effectiveGps = $isSchoolGeofence && (bool) ($company?->gps_enabled ?? false);

        // 1. Evaluate GPS, accuracy & anti-fake GPS guard
        $geoEval = $geoGuard->evaluateCheckinLocation($company, $data, request()?->userAgent(), $effectiveGps);
        $isWithinRadius = $isTestStudent ? true : $geoEval['is_within_radius'];
        $accuracyMeters = $geoEval['accuracy_meters'];
        $isMockDetected = $isTestStudent ? false : $geoEval['is_mock_detected'];

        // Enforce reason when outside radius (or guide to Dinas Luar request)
        if ($effectiveGps && !$isWithinRadius && (empty($data['reason']) || strlen(trim($data['reason'])) < 5)) {
            throw ValidationException::withMessages([
                'reason' => 'Anda terdeteksi berada di luar radius lokasi DU/DI. Presensi kamera selfie hanya berlaku di dalam area kantor penugasan. Silakan ajukan izin melalui menu "Dinas Luar" di Beranda jika sedang bertugas di luar.',
            ]);
        }

        // 2. Resolve Work Hours & Check Late Status
        $workHours = $this->resolveWorkHours($company, $spkl->school_id);

        $checkinStart = Carbon::createFromTimeString($workHours['checkin_start']);
        if (!$isTestStudent && $now->lessThan($checkinStart)) {
            throw ValidationException::withMessages(['time' => 'Tombol presensi baru dapat ditekan mulai pukul ' . substr($workHours['checkin_start'], 0, 5) . ' WIB.']);
        }

        $lateLimit = Carbon::createFromTimeString($workHours['checkin_late']);
        $status = $now->lessThanOrEqualTo($lateLimit) ? 'hadir' : 'terlambat';

        $checkoutNotes = null;
        if ($isTestStudent) {
            $checkoutNotes = '[SIMULASI] Uji Coba Sistem';
            if (!empty($data['reason'])) {
                $checkoutNotes .= ' | ' . $data['reason'];
            }
        } elseif (!$isWithinRadius && !empty($data['reason'])) {
            $checkoutNotes = 'Alasan Luar Radius: ' . $data['reason'];
        }
        if ($isMockDetected) {
            $checkoutNotes = ($checkoutNotes ? $checkoutNotes . ' | ' : '') . '[FLAG] Indikasi Fake GPS / Mock Location Terdeteksi';
        }

        // 3. Atomic check and insert with transaction & duplicate constraint protection
        return DB::transaction(function () use ($spkl, $today, $status, $now, $data, $isWithinRadius, $accuracyMeters, $isMockDetected, $checkoutNotes) {
            $existing = Attendance::where('student_pkl_id', $spkl->id)
                ->forDate($today)
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

    public function recordCheckOut(StudentPkl $spkl, array $data, ?GeolocationGuardService $geoGuard = null): Attendance
    {
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        $company = $spkl->company;
        $geoGuard = $geoGuard ?? app(GeolocationGuardService::class);

        // 1. Evaluate GPS, accuracy & anti-fake GPS guard for check-out
        $isWithinRadius = null;
        $accuracyMeters = null;
        $isMockDetected = false;

        $school = $spkl->school;
        $isTestStudent = $spkl->isTestStudent();
        $isSchoolGeofenceCheckout = $school ? $school->isFeatureEnabled('feature_checkout_geofence', true) : true;
        if ($isTestStudent) {
            $isSchoolGeofenceCheckout = false;
        }
        $effectiveGpsCheckout = $isSchoolGeofenceCheckout && (bool) ($company?->gps_enabled ?? false);

        if ($effectiveGpsCheckout) {
            $geoEval = $geoGuard->evaluateCheckinLocation($company, $data, request()?->userAgent(), true);
            $isWithinRadius = $geoEval['is_within_radius'];
            $accuracyMeters = $geoEval['accuracy_meters'];
            $isMockDetected = $geoEval['is_mock_detected'];

            // Enforce reason when outside radius
            if (!$isWithinRadius && (empty($data['reason']) || strlen(trim($data['reason'])) < 5)) {
                throw ValidationException::withMessages([
                    'reason' => 'Anda berada di luar radius lokasi DU/DI saat absen pulang. Silakan sertakan alasan penugasan luar radius.',
                ]);
            }
        } else {
            $isWithinRadius = true;
            $accuracyMeters = isset($data['accuracy']) ? (int) round((float) $data['accuracy']) : null;
            $isMockDetected = false;
        }

        // 2. Atomic check-out with pessimistic lock
        return DB::transaction(function () use ($spkl, $today, $now, $data, $isWithinRadius, $accuracyMeters, $isMockDetected) {
            $attendance = Attendance::where('student_pkl_id', $spkl->id)
                ->forDate($today)
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

            // Jangan timpa catatan check-in (alasan luar radius & flag Fake GPS) — itu bukti audit.
            $notes = $attendance->checkout_notes;
            if ($isWithinRadius === false && !empty($data['reason'])) {
                $notes = ($notes ? $notes . ' | ' : '') . 'Alasan Pulang Luar Radius: ' . $data['reason'];
            }
            if ($isMockDetected) {
                $notes = ($notes ? $notes . ' | ' : '') . '[FLAG PULANG] Indikasi Fake GPS Terdeteksi';
            }
            $studentNote = trim((string) ($data['checkout_notes'] ?? ''));
            if ($studentNote !== '') {
                $notes = ($notes ? $notes . ' | ' : '') . 'Catatan Pulang: ' . $studentNote;
            }

            $attendance->update([
                'checkout_time' => $now->toTimeString(),
                'checkout_photo_path' => $data['photo_path'] ?? null,
                'checkout_lat' => $data['latitude'] ?? null,
                'checkout_lng' => $data['longitude'] ?? null,
                'is_checkout_within_radius' => $isWithinRadius,
                'checkout_accuracy_meters' => $accuracyMeters,
                'is_checkout_mock_detected' => $isMockDetected,
                'checkout_notes' => $notes,
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
        $settings = SchoolSetting::allForSchool($schoolId);

        return [
            'checkin_start' => $settings['checkin_start'] ?? '06:00:00',
            'checkin_late'  => $settings['checkin_late'] ?? '08:00:00',
            'checkin_alfa'  => $settings['checkin_alfa'] ?? '11:00:00',
            'checkout_start'=> $settings['checkout_start'] ?? '16:00:00',
        ];
    }

    /**
     * Resolve comprehensive day status (holidays, weekends, work hours, checkout status) for student UI.
     */
    public function getDayAttendanceStatus(StudentPkl $spkl, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $dateString = $date->toDateString();
        $holidayService = app(HolidayService::class);
        $isHoliday = $holidayService->isHoliday($spkl->school_id, $date);
        $holiday = null;
        if ($isHoliday) {
            $holiday = \App\Models\SchoolHoliday::withoutGlobalScopes()
                ->where('school_id', $spkl->school_id)
                ->where('holiday_date', $dateString)
                ->first();
        }

        $company = $spkl->company;
        $dayOfWeek = $date->dayOfWeekIso; // 1 (Mon) - 7 (Sun)
        $isWorkDay = true;
        if ($company) {
            $isWorkDay = $company->isWorkDay($date);
        }

        // Jika perusahaan menerapkan 7 hari kerja (on-project), tanggal merah/libur sekolah tidak membebaskan siswa dari kewajiban kerja
        $isEffectiveHoliday = $isHoliday && !($company?->worksOnHolidays());

        $workHours = $company ? $this->resolveWorkHours($company, $spkl->school_id) : null;
        $now = Carbon::now();
        $canCheckout = false;
        $checkoutStartTime = null;

        if ($workHours && isset($workHours['checkout_start'])) {
            $checkoutStartTime = substr($workHours['checkout_start'], 0, 5);
            $checkoutStart = Carbon::createFromTimeString($workHours['checkout_start']);
            $canCheckout = $now->greaterThanOrEqualTo($checkoutStart);
        }

        return [
            'is_holiday' => $isEffectiveHoliday,
            'is_calendar_holiday' => $isHoliday,
            'holiday_title' => $holiday?->title,
            'is_work_day' => $isWorkDay,
            'day_name' => $date->locale('id')->isoFormat('dddd'),
            'work_hours' => $workHours,
            'can_checkout' => $canCheckout,
            'checkout_start_time' => $checkoutStartTime,
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

    /**
     * Override or manually record attendance for a supervised student by Teacher.
     * Useful when student cannot check-in (e.g. broken phone, dead battery, emergency).
     */
    public function overrideAttendanceByTeacher(StudentPkl $spkl, Teacher $teacher, array $data): Attendance
    {
        // 1. Authorize: Ensure student is assigned to this teacher
        if ((int) $spkl->teacher_id !== (int) $teacher->id) {
            throw ValidationException::withMessages([
                'student_pkl_id' => 'Siswa ini bukan merupakan siswa bimbingan Anda.',
            ]);
        }

        // 2. Validate date (cannot be future date)
        $date = $data['date'] ?? Carbon::today()->toDateString();
        $carbonDate = Carbon::parse($date);
        if ($carbonDate->isFuture()) {
            throw ValidationException::withMessages([
                'date' => 'Tanggal presensi tidak boleh melebihi hari ini.',
            ]);
        }

        $status = $data['status'] ?? 'hadir';
        $validStatuses = ['hadir', 'terlambat', 'izin', 'sakit', 'dinas_luar', 'luar_kantor', 'alfa'];
        if (!in_array($status, $validStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'Status kehadiran tidak valid.',
            ]);
        }

        $reason = trim($data['reason'] ?? '');
        if (strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan override wajib diisi minimal 5 karakter (misal: kendala HP siswa rusak).',
            ]);
        }

        $teacherName = $teacher->user->name ?? 'Guru Pembimbing';
        $overrideTag = "[OVERRIDE GURU: {$teacherName}] {$reason}";

        // Normalize time formats
        $checkinTime = !empty($data['checkin_time']) ? Carbon::parse($data['checkin_time'])->toTimeString() : null;
        $checkoutTime = !empty($data['checkout_time']) ? Carbon::parse($data['checkout_time'])->toTimeString() : null;

        // If status is hadir/terlambat and no checkin time provided, default to work hours or now
        if (in_array($status, ['hadir', 'terlambat'], true) && empty($checkinTime)) {
            $company = $spkl->company;
            $workHours = $company ? $this->resolveWorkHours($company, $spkl->school_id) : null;
            $checkinTime = $workHours['checkin_start'] ?? '07:30:00';
        }

        return DB::transaction(function () use ($spkl, $teacher, $date, $status, $checkinTime, $checkoutTime, $overrideTag, $reason) {
            $existing = Attendance::where('student_pkl_id', $spkl->id)
                ->forDate($date)
                ->lockForUpdate()
                ->first();

            $beforeState = $existing ? [
                'status' => $existing->status,
                'checkin_time' => $existing->checkin_time,
                'checkout_time' => $existing->checkout_time,
                'checkout_notes' => $existing->checkout_notes,
            ] : null;

            if ($existing) {
                $existing->update([
                    'status' => $status,
                    'checkin_time' => $checkinTime ?? $existing->checkin_time,
                    'checkout_time' => $checkoutTime ?? $existing->checkout_time,
                    'checkout_notes' => $overrideTag,
                    'is_within_radius' => true,
                ]);
                $attendance = $existing;
            } else {
                $attendance = Attendance::create([
                    'school_id' => $spkl->school_id,
                    'student_pkl_id' => $spkl->id,
                    'date' => $date,
                    'status' => $status,
                    'checkin_time' => $checkinTime,
                    'checkout_time' => $checkoutTime,
                    'checkout_notes' => $overrideTag,
                    'is_within_radius' => true,
                ]);
            }

            AuditLogService::log(
                'attendance_override',
                'attendance',
                "Guru pembimbing {$teacher->user->name} melakukan override presensi {$status} untuk siswa {$spkl->student->user->name} tanggal {$date}. Alasan: {$reason}",
                $spkl->school,
                Auth::user(),
                $beforeState,
                [
                    'status' => $status,
                    'checkin_time' => $attendance->checkin_time,
                    'checkout_time' => $attendance->checkout_time,
                    'checkout_notes' => $attendance->checkout_notes,
                    'reason' => $reason,
                ]
            );

            return $attendance;
        });
    }
}
