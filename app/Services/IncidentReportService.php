<?php

namespace App\Services;

use App\Models\IncidentFollowup;
use App\Models\IncidentReport;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\EmergencyIncidentReportedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IncidentReportService
{
    /**
     * Create student incident / SOS report with notification delivery.
     * Note: strictly restricted from DU/DI visibility.
     */
    public function createReport(StudentPkl $spkl, array $payload, ?UploadedFile $file = null): IncidentReport
    {
        $attachmentPath = null;
        if ($file) {
            $fileName = 'schools/' . $spkl->school_id . '/incidents/' . date('Y') . '/' . Str::random(24) . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->put($fileName, file_get_contents($file));
            $attachmentPath = $fileName;
        }

        $report = IncidentReport::create([
            'school_id' => $spkl->school_id,
            'student_pkl_id' => $spkl->id,
            'category' => $payload['category'],
            'urgency' => $payload['urgency'] ?? 'medium',
            'subject' => $payload['subject'],
            'description' => $payload['description'],
            'attachment_path' => $attachmentPath,
            'status' => 'submitted',
        ]);

        // Trigger notifications to Guru Pembimbing and School Admin
        $this->notifySupervisingStaff($report, $spkl);

        return $report;
    }

    /**
     * Add follow-up note and optionally transition report status.
     */
    public function addFollowup(IncidentReport $report, User $handler, string $note, ?string $newStatus = null): IncidentFollowup
    {
        return DB::transaction(function () use ($report, $handler, $note, $newStatus) {
            $followup = IncidentFollowup::create([
                'incident_report_id' => $report->id,
                'user_id' => $handler->id,
                'action_note' => $note,
                'created_at' => now(),
            ]);

            $updateData = ['handler_id' => $handler->id];
            if ($newStatus) {
                $updateData['status'] = $newStatus;
                if ($newStatus === 'resolved') {
                    $updateData['resolved_at'] = now();
                }
            }

            $report->update($updateData);

            return $followup;
        });
    }

    /**
     * Get incident reports list assigned to a teacher's students.
     */
    public function getIncidentsForTeacher(Teacher $teacher, array $filters = []): LengthAwarePaginator
    {
        $spklIds = StudentPkl::where('teacher_id', $teacher->id)->pluck('id');

        $query = IncidentReport::whereIn('student_pkl_id', $spklIds)
            ->with(['studentPkl.student.user', 'studentPkl.company', 'handler', 'followups.user'])
            ->orderByRaw("CASE WHEN status = 'submitted' THEN 1 WHEN status = 'in_progress' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE WHEN urgency = 'critical' THEN 1 WHEN urgency = 'high' THEN 2 WHEN urgency = 'medium' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['urgency'])) {
            $query->where('urgency', $filters['urgency']);
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * Get all incident reports for a school (Admin Sekolah view).
     */
    public function getIncidentsForSchool(int $schoolId, array $filters = []): LengthAwarePaginator
    {
        $query = IncidentReport::where('school_id', $schoolId)
            ->with(['studentPkl.student.user', 'studentPkl.company', 'studentPkl.teacher.user', 'handler', 'followups.user'])
            ->orderByRaw("CASE WHEN status = 'submitted' THEN 1 WHEN status = 'in_progress' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE WHEN urgency = 'critical' THEN 1 WHEN urgency = 'high' THEN 2 WHEN urgency = 'medium' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['urgency'])) {
            $query->where('urgency', $filters['urgency']);
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * Get student's own reports history.
     */
    public function getIncidentsForStudent(StudentPkl $spkl): Collection
    {
        return IncidentReport::where('student_pkl_id', $spkl->id)
            ->with(['followups.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Notify teacher and school admin upon incident report creation.
     */
    protected function notifySupervisingStaff(IncidentReport $report, StudentPkl $spkl): void
    {
        $notification = new EmergencyIncidentReportedNotification($report);

        // 1. Notify student's supervising teacher
        $teacherUser = $spkl->teacher?->user;
        if ($teacherUser) {
            $teacherUser->notify($notification);
        }

        // 2. Notify school admins
        $admins = User::where('school_id', $spkl->school_id)
            ->where('role', 'admin-sekolah')
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }
    }
}
