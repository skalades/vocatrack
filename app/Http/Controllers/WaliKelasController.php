<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentPasswordResetRequest;
use App\Services\AuditLogService;
use App\Services\HomeroomClassService;
use App\Services\TenantProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WaliKelasController extends Controller
{
    public function __construct(
        protected HomeroomClassService $homeroomService
    ) {}

    protected function getSchoolId(): int
    {
        return (int) Auth::user()->school_id;
    }

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $schoolId = $this->getSchoolId();
        $scope = $this->homeroomService->resolveScope($user, $request->integer('class_id') ?: null);

        $data = $this->homeroomService->getDashboardData($schoolId, $scope['targetClassIds'], $user);

        return view('walikelas.dashboard', array_merge($data, [
            'classes' => $scope['classes'],
            'selectedClass' => $scope['selectedClass'],
            'activeClassFilter' => $request->integer('class_id') ?: null,
        ]));
    }

    public function students(Request $request)
    {
        $user = Auth::user();
        $schoolId = $this->getSchoolId();
        $scope = $this->homeroomService->resolveScope($user, $request->integer('class_id') ?: null);

        $students = $this->homeroomService->getStudents($schoolId, $scope['targetClassIds'], [
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ]);

        return view('walikelas.students', [
            'classes' => $scope['classes'],
            'selectedClass' => $scope['selectedClass'],
            'students' => $students,
        ]);
    }

    public function attendance(Request $request)
    {
        $user = Auth::user();
        $schoolId = $this->getSchoolId();
        $scope = $this->homeroomService->resolveScope($user, $request->integer('class_id') ?: null);

        $result = $this->homeroomService->getAttendances($schoolId, $scope['targetClassIds'], [
            'date' => $request->input('date'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ]);

        return view('walikelas.attendance', [
            'classes' => $scope['classes'],
            'selectedClass' => $scope['selectedClass'],
            'attendances' => $result['attendances'],
            'stats' => $result['stats'],
            'selectedDate' => $result['selectedDate'],
        ]);
    }

    public function journals(Request $request)
    {
        $user = Auth::user();
        $schoolId = $this->getSchoolId();
        $scope = $this->homeroomService->resolveScope($user, $request->integer('class_id') ?: null);

        $journals = $this->homeroomService->getJournals($schoolId, $scope['targetClassIds'], [
            'status' => $request->input('status'),
            'date' => $request->input('date'),
        ]);

        return view('walikelas.journals', [
            'classes' => $scope['classes'],
            'selectedClass' => $scope['selectedClass'],
            'journals' => $journals,
        ]);
    }

    public function incidents(Request $request)
    {
        $user = Auth::user();
        $schoolId = $this->getSchoolId();
        $scope = $this->homeroomService->resolveScope($user, $request->integer('class_id') ?: null);

        $incidents = $this->homeroomService->getIncidents($schoolId, $scope['targetClassIds'], [
            'status' => $request->input('status'),
        ]);

        return view('walikelas.incidents', [
            'classes' => $scope['classes'],
            'selectedClass' => $scope['selectedClass'],
            'incidents' => $incidents,
        ]);
    }

    public function grades(Request $request)
    {
        $user = Auth::user();
        $schoolId = $this->getSchoolId();
        $scope = $this->homeroomService->resolveScope($user, $request->integer('class_id') ?: null);

        $query = $this->homeroomService->getGradesQuery($schoolId, $scope['targetClassIds']);

        if ($request->get('export') === 'csv') {
            $slug = Str::slug($scope['selectedClass']?->name ?? 'kelas');
            return $this->homeroomService->exportGradesCsvStream($query, $slug);
        }

        $records = $query->paginate(20)->withQueryString();

        return view('walikelas.grades', [
            'classes' => $scope['classes'],
            'selectedClass' => $scope['selectedClass'],
            'records' => $records,
        ]);
    }

    /**
     * Reset kata sandi siswa dalam rombel yang diampu Wali Kelas.
     */
    public function resetStudentPassword(Request $request, Student $student)
    {
        $user = Auth::user();
        $schoolId = $this->getSchoolId();
        $homeroomClassIds = $user->homeroom_class_ids;

        abort_unless(
            $student->user?->school_id === $schoolId && in_array((int) $student->class_id, $homeroomClassIds, true),
            403,
            'Siswa bukan berasal dari rombel kelas yang Anda ampu.'
        );
        abort_unless($student->user?->role === 'siswa', 403, 'Akun target bukan akun siswa.');

        $validated = $request->validate([
            'new_password' => ['nullable', 'string', 'min:8', 'max:100'],
        ], [
            'new_password.min' => 'Kata sandi baru minimal 8 karakter. Kosongkan untuk dibuatkan otomatis.',
        ]);

        $targetUser = $student->user;
        $newPassword = !empty($validated['new_password'])
            ? $validated['new_password']
            : TenantProvisioningService::generateTemporaryPassword();

        DB::transaction(function () use ($targetUser, $newPassword, $student, $user) {
            $targetUser->update([
                'password' => $newPassword,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $targetUser->id)->delete();
            }

            StudentPasswordResetRequest::where('student_id', $student->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'completed',
                    'notes' => 'Direset oleh Wali Kelas ' . $user->name . ' pada ' . now()->format('d/m/Y H:i'),
                ]);

            AuditLogService::log(
                'password_reset',
                'security',
                "Kata sandi siswa ({$targetUser->email}) direset oleh Wali Kelas {$user->email}.",
                null,
                $user,
                null,
                ['target_user_id' => $targetUser->id, 'student_id' => $student->id]
            );
        });

        return back()
            ->with('success', "Kata sandi {$targetUser->name} berhasil direset. Siswa wajib menggantinya saat login berikutnya.")
            ->with('temporary_credential', [
                'email' => $targetUser->email,
                'password' => $newPassword,
                'context' => "Peserta Didik {$targetUser->name}",
            ]);
    }
}
