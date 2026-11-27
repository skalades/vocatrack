<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceEscalation;
use App\Models\AttendanceRequest;
use App\Models\Journal;
use App\Models\SchoolSetting;
use App\Models\StudentPkl;
use App\Notifications\AttendanceEscalationNotification;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminAttendanceMonitoringService
{
    public function __construct(
        protected HolidayService $holidayService,
        protected AttendanceService $attendanceService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Kumpulkan data monitoring kepatuhan harian (absen masuk, absen pulang, dan jurnal)
     * beserta filter per jurusan, perusahaan, guru pembimbing, serta status kepatuhan.
     *
     * @return array{
     *     date: string,
     *     is_school_holiday: bool,
     *     rows: LengthAwarePaginator,
     *     stats: array<string, int>,
     *     compliance: string
     * }
     */
    public function getDailyMonitoringData(int $schoolId, array $filters = [], int $perPage = 25): array
    {
        $date = !empty($filters['date']) ? Carbon::parse($filters['date']) : Carbon::today();
        $dateString = $date->toDateString();
        $isSchoolHoliday = $this->holidayService->isHoliday($schoolId, $date);
        $compliance = (string) ($filters['compliance'] ?? 'all');

        $query = StudentPkl::where('school_id', $schoolId)
            ->where('status', 'active')
            ->with([
                'student.user.profile',
                'student.class.major',
                'company',
                'teacher.user.profile',
                'pklPeriod',
            ]);

        if (!empty($filters['major_id']) && $filters['major_id'] !== 'all') {
            $majorId = (int) $filters['major_id'];
            $query->whereHas('student.class', fn ($q) => $q->where('major_id', $majorId));
        }

        if (!empty($filters['company_id']) && $filters['company_id'] !== 'all') {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (!empty($filters['teacher_id']) && $filters['teacher_id'] !== 'all') {
            $query->where('teacher_id', (int) $filters['teacher_id']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('student.user', fn ($sq) => $sq->where('name', 'like', $search))
                    ->orWhereHas('student', fn ($sq) => $sq->where('nisn', 'like', $search)->orWhere('nis', 'like', $search))
                    ->orWhereHas('company', fn ($cq) => $cq->where('name', 'like', $search))
                    ->orWhereHas('teacher.user', fn ($tq) => $tq->where('name', 'like', $search));
            });
        }

        $activePlacements = $query->get()
            ->filter(fn (StudentPkl $spkl) => $spkl->coversDate($date))
            ->values();

        $spklIds = $activePlacements->pluck('id')->all();

        $attendances = empty($spklIds)
            ? collect()
            : Attendance::whereIn('student_pkl_id', $spklIds)
                ->whereDate('date', $dateString)
                ->get()
                ->keyBy('student_pkl_id');

        $journals = empty($spklIds)
            ? collect()
            : Journal::whereIn('student_pkl_id', $spklIds)
                ->whereDate('date', $dateString)
                ->get()
                ->keyBy('student_pkl_id');

        $approvedRequests = empty($spklIds)
            ? collect()
            : AttendanceRequest::whereIn('student_pkl_id', $spklIds)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $dateString)
                ->whereDate('end_date', '>=', $dateString)
                ->get()
                ->keyBy('student_pkl_id');

        $escalationsBySpkl = empty($spklIds)
            ? collect()
            : AttendanceEscalation::whereIn('student_pkl_id', $spklIds)
                ->whereDate('date', $dateString)
                ->with('escalatedBy')
                ->latest('created_at')
                ->get()
                ->groupBy('student_pkl_id');

        $workHoursCache = [];

        $evaluatedRows = $activePlacements->map(function (StudentPkl $spkl) use (
            $date,
            $dateString,
            $schoolId,
            $isSchoolHoliday,
            $attendances,
            $journals,
            $approvedRequests,
            $escalationsBySpkl,
            &$workHoursCache
        ) {
            return $this->evaluatePlacementRow(
                $spkl,
                $date,
                $dateString,
                $schoolId,
                $isSchoolHoliday,
                $attendances->get($spkl->id),
                $journals->get($spkl->id),
                $approvedRequests->get($spkl->id),
                $escalationsBySpkl->get($spkl->id, collect()),
                $workHoursCache
            );
        });

        $stats = [
            'total' => $evaluatedRows->count(),
            'expected' => $evaluatedRows->where('is_exempt', false)->count(),
            'missing_checkin' => $evaluatedRows->where('missing_checkin', true)->count(),
            'missing_checkout' => $evaluatedRows->where('missing_checkout', true)->count(),
            'missing_journal' => $evaluatedRows->where('missing_journal', true)->count(),
            'needs_attention' => $evaluatedRows->where('needs_attention', true)->count(),
            'complete' => $evaluatedRows->where('is_complete', true)->count(),
            'exempt' => $evaluatedRows->where('is_exempt', true)->count(),
        ];

        $filteredRows = match ($compliance) {
            'missing_checkin' => $evaluatedRows->where('missing_checkin', true),
            'missing_checkout' => $evaluatedRows->where('missing_checkout', true),
            'missing_journal' => $evaluatedRows->where('missing_journal', true),
            'needs_attention' => $evaluatedRows->where('needs_attention', true),
            'complete' => $evaluatedRows->where('is_complete', true),
            'exempt' => $evaluatedRows->where('is_exempt', true),
            default => $evaluatedRows,
        };

        // Urutkan siswa yang butuh perhatian ke posisi paling atas
        $sortedRows = $filteredRows->sortBy([
            fn (array $a, array $b) => $b['attention_score'] <=> $a['attention_score'],
            fn (array $a, array $b) => strcmp(mb_strtolower($a['student_name']), mb_strtolower($b['student_name'])),
        ])->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $sortedRows->forPage($page, $perPage)->values(),
            $sortedRows->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );

        return [
            'date' => $dateString,
            'is_school_holiday' => $isSchoolHoliday,
            'rows' => $paginator,
            'stats' => $stats,
            'compliance' => $compliance,
        ];
    }

    /**
     * Kirim eskalasi dari Admin Sekolah kepada Guru Pembimbing untuk mengingatkan siswa.
     *
     * @param  array<int, string>  $missingTypes
     */
    public function escalateToTeacher(
        int $schoolId,
        int $adminUserId,
        StudentPkl $spkl,
        string $date,
        array $missingTypes,
        ?string $note = null
    ): AttendanceEscalation {
        if ($spkl->school_id !== $schoolId) {
            abort(403, 'Akses ditolak.');
        }

        $spkl->loadMissing([
            'student.user.profile',
            'student.class.major',
            'company',
            'teacher.user.profile',
        ]);

        $teacherUser = $spkl->teacher?->user;
        if (!$teacherUser) {
            throw ValidationException::withMessages([
                'student_pkl_id' => 'Siswa ini belum memiliki Guru Pembimbing yang ditugaskan.',
            ]);
        }

        $normalizedTypes = array_values(array_unique(array_intersect(
            $missingTypes,
            array_keys(AttendanceEscalation::TYPE_LABELS)
        )));

        if (empty($normalizedTypes)) {
            throw ValidationException::withMessages([
                'missing_types' => 'Pilih minimal satu poin pengingat (belum masuk, belum pulang, atau belum jurnal).',
            ]);
        }

        $teacherPhone = $teacherUser->wa_number ?: ($teacherUser->profile?->phone ?? null);
        $settings = SchoolSetting::allForSchool($schoolId);
        $isWaEnabled = isset($settings['feature_whatsapp'])
            ? filter_var($settings['feature_whatsapp'], FILTER_VALIDATE_BOOLEAN)
            : true;
        $willAttemptWa = $isWaEnabled && WhatsAppGatewayService::isValidIndonesianNumber($teacherPhone);

        $escalation = AttendanceEscalation::create([
            'school_id' => $schoolId,
            'student_pkl_id' => $spkl->id,
            'teacher_id' => $spkl->teacher_id,
            'escalated_by_user_id' => $adminUserId,
            'date' => Carbon::parse($date)->toDateString(),
            'missing_types' => $normalizedTypes,
            'note' => $note ? trim($note) : null,
            'sent_in_app' => true,
            'sent_whatsapp' => $willAttemptWa,
        ]);

        $this->notificationService->sendToUser(
            $teacherUser,
            new AttendanceEscalationNotification($escalation)
        );

        return $escalation;
    }

    /**
     * Evaluasi satu penempatan siswa untuk tanggal tertentu menjadi payload baris UI & modal.
     */
    protected function evaluatePlacementRow(
        StudentPkl $spkl,
        Carbon $date,
        string $dateString,
        int $schoolId,
        bool $isSchoolHoliday,
        ?Attendance $attendance,
        ?Journal $journal,
        ?AttendanceRequest $approvedRequest,
        Collection $escalations,
        array &$workHoursCache
    ): array {
        $student = $spkl->student;
        $studentUser = $student?->user;
        $company = $spkl->company;
        $teacherUser = $spkl->teacher?->user;
        $class = $student?->class;
        $major = $class?->major;

        $isCompanyStarted = (bool) ($company?->is_pkl_started ?? false);
        $isWorkDay = $company ? $company->isWorkDay($date) : true;
        $isEffectiveHoliday = $isSchoolHoliday && !($company?->worksOnHolidays() ?? false);

        $workHours = $company
            ? ($workHoursCache[$company->id] ??= $this->attendanceService->resolveWorkHours($company, $schoolId))
            : null;

        $isLeaveStatus = $attendance && in_array($attendance->status, ['izin', 'sakit', 'dinas_luar', 'luar_kantor'], true);
        $isApprovedLeave = $isLeaveStatus || (!$attendance && $approvedRequest !== null);

        $exemptReason = null;
        if (!$isCompanyStarted) {
            $exemptReason = 'PKL Belum Mulai';
        } elseif ($isEffectiveHoliday) {
            $exemptReason = 'Libur Sekolah';
        } elseif (!$isWorkDay) {
            $exemptReason = 'Hari Off DU/DI';
        } elseif ($isApprovedLeave) {
            $leaveType = $attendance?->status ?? $approvedRequest?->type ?? 'izin';
            $exemptReason = 'Izin / ' . Str::title(str_replace('_', ' ', $leaveType));
        }

        $isExempt = $exemptReason !== null;

        $missingCheckin = !$isExempt && (!$attendance || $attendance->status === 'alfa');
        $hasValidCheckout = $attendance && !empty($attendance->checkout_time) && $attendance->checkout_time !== '00:00:00';
        $missingCheckout = !$isExempt
            && $attendance
            && in_array($attendance->status, ['hadir', 'terlambat'], true)
            && !$hasValidCheckout;
        $missingJournal = !$isExempt && !$journal;

        $missingTypes = [];
        if ($missingCheckin) {
            $missingTypes[] = 'checkin';
        }
        if ($missingCheckout) {
            $missingTypes[] = 'checkout';
        }
        if ($missingJournal) {
            $missingTypes[] = 'journal';
        }

        $needsAttention = !$isExempt && !empty($missingTypes);
        $isComplete = !$isExempt && empty($missingTypes);

        $attentionScore = ($missingCheckin ? 4 : 0) + ($missingCheckout ? 2 : 0) + ($missingJournal ? 1 : 0);

        $studentPhone = $studentUser?->wa_number ?: ($studentUser?->profile?->phone ?? null);
        $teacherPhone = $teacherUser?->wa_number ?: ($teacherUser?->profile?->phone ?? null);
        $normalizedTeacherWa = WhatsAppGatewayService::normalizePhoneNumber($teacherPhone);

        $missingLabels = array_map(
            fn (string $t) => AttendanceEscalation::TYPE_LABELS[$t] ?? $t,
            $missingTypes
        );

        $waMessage = "Yth. Bapak/Ibu " . ($teacherUser?->name ?? 'Guru Pembimbing') . ", mohon bantuannya mengingatkan siswa bimbingan:\n"
            . "- Nama: " . ($studentUser?->name ?? '-') . " (" . ($class?->name ?? '-') . " / " . ($major?->name ?? '-') . ")\n"
            . "- DU/DI: " . ($company?->name ?? '-') . "\n"
            . "- Tanggal: {$dateString}\n"
            . "- Status: " . (!empty($missingLabels) ? implode(', ', $missingLabels) : 'Perlu pengecekan kepatuhan') . "\n"
            . ($studentPhone ? "- Kontak Siswa: {$studentPhone}" : '');

        $teacherWaUrl = $normalizedTeacherWa
            ? 'https://wa.me/' . $normalizedTeacherWa . '?text=' . rawurlencode($waMessage)
            : null;

        $checkinStatusLabel = match (true) {
            $attendance && $attendance->status === 'hadir' => 'Hadir (' . substr((string) $attendance->checkin_time, 0, 5) . ')',
            $attendance && $attendance->status === 'terlambat' => 'Terlambat (' . substr((string) $attendance->checkin_time, 0, 5) . ')',
            $attendance && $attendance->status === 'alfa' => 'Alfa',
            $isApprovedLeave => $exemptReason,
            $isExempt => $exemptReason,
            default => 'Belum Absen Masuk',
        };

        $checkoutStatusLabel = match (true) {
            $hasValidCheckout => 'Sudah Pulang (' . substr((string) $attendance->checkout_time, 0, 5) . ')',
            $missingCheckout => 'Belum Absen Pulang',
            $missingCheckin => 'Menunggu Masuk',
            $isExempt => $exemptReason,
            default => '-',
        };

        $journalStatusLabel = match (true) {
            $journal !== null => 'Sudah Isi (' . Str::title(str_replace('_', ' ', $journal->status)) . ')',
            $isExempt => $exemptReason,
            default => 'Belum Isi Jurnal',
        };

        $latestEscalation = $escalations->first();

        return [
            'spkl_id' => $spkl->id,
            'student_name' => $studentUser?->name ?? 'Siswa',
            'student_nis' => $student?->nis ?? '-',
            'student_nisn' => $student?->nisn ?? '-',
            'student_phone' => $studentPhone ?? '-',
            'class_name' => $class?->name ?? '-',
            'major_id' => $major?->id,
            'major_name' => $major?->name ?? '-',
            'major_code' => $major?->code ?? '-',
            'company_id' => $company?->id,
            'company_name' => $company?->name ?? '-',
            'company_address' => $company?->address ?? '-',
            'company_pic' => $company?->contact_person ?? '-',
            'company_phone' => $company?->phone ?? '-',
            'company_work_days' => $company?->work_days_label ?? '-',
            'company_work_hours' => $workHours
                ? substr((string) ($workHours['checkin_start'] ?? '06:00'), 0, 5) . ' - ' . substr((string) ($workHours['checkout_start'] ?? '16:00'), 0, 5)
                : '-',
            'teacher_id' => $spkl->teacher_id,
            'teacher_name' => $teacherUser?->name ?? 'Belum Ditugaskan',
            'teacher_nip' => $spkl->teacher?->nip ?? '-',
            'teacher_phone' => $teacherPhone ?? '-',
            'teacher_wa_url' => $teacherWaUrl,
            'date' => $dateString,
            'is_exempt' => $isExempt,
            'exempt_reason' => $exemptReason,
            'missing_checkin' => $missingCheckin,
            'missing_checkout' => $missingCheckout,
            'missing_journal' => $missingJournal,
            'missing_types' => $missingTypes,
            'needs_attention' => $needsAttention,
            'is_complete' => $isComplete,
            'attention_score' => $attentionScore,
            'checkin_status_label' => $checkinStatusLabel,
            'checkout_status_label' => $checkoutStatusLabel,
            'journal_status_label' => $journalStatusLabel,
            'attendance_status' => $attendance?->status,
            'checkin_time' => $attendance?->checkin_time ? substr((string) $attendance->checkin_time, 0, 5) : null,
            'checkout_time' => $hasValidCheckout ? substr((string) $attendance->checkout_time, 0, 5) : null,
            'journal_summary' => $journal ? Str::limit((string) $journal->activity_description, 160) : null,
            'journal_status' => $journal?->status,
            'escalation_count' => $escalations->count(),
            'latest_escalation' => $latestEscalation ? [
                'time' => $latestEscalation->created_at?->format('H:i'),
                'by' => $latestEscalation->escalatedBy?->name ?? 'Admin',
                'missing_labels' => $latestEscalation->missing_labels,
                'note' => $latestEscalation->note,
                'sent_whatsapp' => $latestEscalation->sent_whatsapp,
            ] : null,
            'escalations' => $escalations->map(fn (AttendanceEscalation $esc) => [
                'time' => $esc->created_at?->format('d M Y H:i'),
                'by' => $esc->escalatedBy?->name ?? 'Admin',
                'missing_labels' => $esc->missing_labels,
                'note' => $esc->note,
                'sent_whatsapp' => $esc->sent_whatsapp,
            ])->values()->all(),
        ];
    }
}
