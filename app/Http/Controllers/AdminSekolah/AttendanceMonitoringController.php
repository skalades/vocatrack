<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Models\AttendanceEscalation;
use App\Models\Company;
use App\Models\Major;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Services\AdminAttendanceMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AttendanceMonitoringController extends BaseAdminSekolahController
{
    public function index(Request $request, AdminAttendanceMonitoringService $monitoringService)
    {
        $schoolId = $this->getSchoolId();

        $monitoring = $monitoringService->getDailyMonitoringData($schoolId, $request->only([
            'date',
            'major_id',
            'company_id',
            'teacher_id',
            'compliance',
            'search',
        ]));

        $majors = Major::where('school_id', $schoolId)->orderBy('name')->get();
        $companies = Company::where('school_id', $schoolId)->orderBy('name')->get();
        $teachers = Teacher::whereHas('user', fn ($q) => $q->where('school_id', $schoolId))
            ->with('user')
            ->get()
            ->sortBy(fn ($t) => mb_strtolower($t->user->name ?? ''))
            ->values();

        return view('adminsekolah.attendance-monitoring', [
            'date' => $monitoring['date'],
            'isSchoolHoliday' => $monitoring['is_school_holiday'],
            'rows' => $monitoring['rows'],
            'stats' => $monitoring['stats'],
            'compliance' => $monitoring['compliance'],
            'majors' => $majors,
            'companies' => $companies,
            'teachers' => $teachers,
        ]);
    }

    public function escalate(Request $request, AdminAttendanceMonitoringService $monitoringService)
    {
        $schoolId = $this->getSchoolId();

        $validated = $request->validate([
            'student_pkl_id' => [
                'required',
                Rule::exists('student_pkl', 'id')->where('school_id', $schoolId),
            ],
            'date' => ['required', 'date'],
            'missing_types' => ['required', 'array', 'min:1'],
            'missing_types.*' => ['in:' . implode(',', array_keys(AttendanceEscalation::TYPE_LABELS))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $spkl = StudentPkl::where('school_id', $schoolId)->findOrFail($validated['student_pkl_id']);

        $escalation = $monitoringService->escalateToTeacher(
            $schoolId,
            (int) Auth::id(),
            $spkl,
            $validated['date'],
            $validated['missing_types'],
            $validated['note'] ?? null
        );

        $teacherName = $spkl->teacher?->user?->name ?? 'Guru Pembimbing';
        $studentName = $spkl->student?->user?->name ?? 'Siswa';
        $channelInfo = $escalation->sent_whatsapp
            ? 'melalui notifikasi aplikasi & WhatsApp Gateway'
            : 'melalui notifikasi aplikasi (nomor WA guru belum tersedia atau gateway nonaktif)';

        return back()->with('success', "Eskalasi untuk {$studentName} telah dikirim ke {$teacherName} {$channelInfo}.");
    }
}
