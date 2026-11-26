<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Exports\LeadershipCredentialsExport;
use App\Exports\TeacherCredentialsExport;
use App\Http\Requests\AdminSekolah\StoreTeacherRequest;
use App\Http\Requests\AdminSekolah\UpdateTeacherRequest;
use App\Models\Major;
use App\Models\Profile;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\TenantProvisioningService;
use App\Services\WhatsAppGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class TeacherController extends BaseAdminSekolahController
{
    public function teachers(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $search = trim((string) $request->query('search', ''));
        $selectedMajor = $request->query('major');

        $query = Teacher::whereHas('user', fn($q) => $q->where('school_id', $schoolId))
            ->with(['user.profile', 'studentPkls.student.user', 'majors', 'headedMajors', 'homeroomClasses'])
            ->applyAdminFilters($search, $selectedMajor);

        $teachers = $query->latest('teachers.id')->paginate(15)->withQueryString();
        $majors = Major::where('school_id', $schoolId)->orderBy('name')->get();

        // Exact DB metrics across the entire school (Scalable)
        $totalTeachers = Teacher::whereHas('user', fn($q) => $q->where('school_id', $schoolId))->count();
        $totalPlotted = StudentPkl::where('school_id', $schoolId)->whereIn('status', ['assigned', 'active'])->count();
        $avgPlotted = $totalTeachers > 0 ? round($totalPlotted / $totalTeachers, 1) : 0;
        $activeTeachersCount = Teacher::whereHas('user', fn($q) => $q->where('school_id', $schoolId))
            ->whereHas('studentPkls', fn($q) => $q->whereIn('status', ['assigned', 'active']))
            ->count();

        return view('adminsekolah.teachers', compact('teachers', 'totalTeachers', 'totalPlotted', 'avgPlotted', 'activeTeachersCount', 'majors', 'selectedMajor'));
    }

    public function exportTeachers(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $majorPart = $this->resolveMajorSlugPart($schoolId, $request);
        $filters = [
            'search' => $request->query('search'),
            'major' => $request->query('major'),
        ];

        AuditLogService::log(
            'teacher_credentials_export',
            'export',
            "Mengekspor data akun dan akses guru pembimbing (Filter: {$majorPart})."
        );

        return $this->downloadSpreadsheet(
            new TeacherCredentialsExport($schoolId, $filters),
            "Akun_Akses_Guru_{$majorPart}",
            $request->query('format')
        );
    }

    public function exportLeadership(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $role = match ($request->query('role')) {
            LeadershipCredentialsExport::ROLE_KAJUR => LeadershipCredentialsExport::ROLE_KAJUR,
            LeadershipCredentialsExport::ROLE_WALIKELAS => LeadershipCredentialsExport::ROLE_WALIKELAS,
            default => LeadershipCredentialsExport::ROLE_ALL,
        };

        $majorPart = $this->resolveMajorSlugPart($schoolId, $request);
        $filters = [
            'search' => $request->query('search'),
            'major' => $request->query('major'),
        ];

        $roleLabel = match ($role) {
            LeadershipCredentialsExport::ROLE_KAJUR => 'Kajur',
            LeadershipCredentialsExport::ROLE_WALIKELAS => 'Wali Kelas',
            default => 'Kajur & Wali Kelas',
        };
        $filePrefix = match ($role) {
            LeadershipCredentialsExport::ROLE_KAJUR => "Akun_Akses_Kajur_{$majorPart}",
            LeadershipCredentialsExport::ROLE_WALIKELAS => "Akun_Akses_Wali_Kelas_{$majorPart}",
            default => "Akun_Akses_Kajur_Wali_Kelas_{$majorPart}",
        };

        AuditLogService::log(
            'leadership_credentials_export',
            'export',
            "Mengekspor data akun dan akses {$roleLabel} (Filter: {$majorPart})."
        );

        return $this->downloadSpreadsheet(
            new LeadershipCredentialsExport($schoolId, $filters, $role),
            $filePrefix,
            $request->query('format')
        );
    }

    private function resolveMajorSlugPart(int $schoolId, Request $request): string
    {
        $major = $request->query('major');
        if ($major === 'lintas') {
            return 'Lintas_Jurusan';
        }
        if ($major && $major !== 'all') {
            $majorModel = Major::where('school_id', $schoolId)->find($major);
            if ($majorModel) {
                return Str::slug($majorModel->name, '_');
            }
        }

        return 'Semua_Jurusan';
    }

    private function downloadSpreadsheet(object $export, string $baseName, ?string $format)
    {
        $filename = $baseName . '_' . date('Ymd_His');

        if ($format === 'csv') {
            return Excel::download($export, "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, "{$filename}.xlsx");
    }

    public function storeTeacher(StoreTeacherRequest $request)
    {
        $schoolId = $this->getSchoolId();
        $normalizedPhone = WhatsAppGatewayService::normalizePhoneNumber($request->phone);

        DB::transaction(function () use ($request, $schoolId, $normalizedPhone) {
            $user = User::create([
                'school_id' => $schoolId,
                'name' => $request->name,
                'email' => $request->email,
                'wa_number' => $normalizedPhone,
                'password' => Hash::make('password'),
                'role' => 'guru-pembimbing',
                'is_active' => true,
            ]);

            Profile::create([
                'user_id' => $user->id,
                'gender' => 'L',
                'phone' => $normalizedPhone ?: $request->phone,
            ]);

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'nip' => $request->nip,
            ]);

            if ($request->has('major_ids')) {
                $teacher->majors()->sync($request->major_ids);
            }
        });

        return back()->with('success', 'Akun Guru Pembimbing berhasil dibuat.');
    }

    public function updateTeacher(UpdateTeacherRequest $request, Teacher $teacher)
    {
        $schoolId = $this->getSchoolId();
        abort_if($teacher->user->school_id !== $schoolId, 403);

        $normalizedPhone = WhatsAppGatewayService::normalizePhoneNumber($request->phone);

        DB::transaction(function () use ($request, $teacher, $normalizedPhone) {
            $teacher->user->update([
                'name' => $request->name,
                'email' => $request->email,
                'wa_number' => $normalizedPhone,
            ]);

            $teacher->user->profile()->updateOrCreate(
                ['user_id' => $teacher->user_id],
                ['phone' => $normalizedPhone ?: $request->phone]
            );

            $teacher->update([
                'nip' => $request->nip,
            ]);

            $teacher->majors()->sync($request->major_ids ?? []);
        });

        return back()->with('success', 'Data guru pembimbing berhasil diperbarui.');
    }

    public function resetTeacherPassword(Request $request, Teacher $teacher)
    {
        $schoolId = $this->getSchoolId();
        abort_if($teacher->user->school_id !== $schoolId, 403);

        $validated = $request->validate([
            'new_password' => ['nullable', 'string', 'min:8', 'max:100'],
        ], [
            'new_password.min' => 'Kata sandi baru minimal 8 karakter. Kosongkan untuk dibuatkan otomatis.',
        ]);

        $user = $teacher->user;
        $newPassword = $validated['new_password'] ?? TenantProvisioningService::generateTemporaryPassword();

        $user->update([
            'password' => $newPassword,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        AuditLogService::log(
            'password_reset',
            'security',
            "Kata sandi guru pembimbing ({$user->email}) direset oleh Admin Sekolah " . Auth::user()->email . '.'
        );

        return back()
            ->with('success', "Kata sandi {$user->name} berhasil direset. Guru wajib menggantinya saat login berikutnya.")
            ->with('temporary_credential', [
                'email' => $user->email,
                'password' => $newPassword,
                'context' => "Guru Pembimbing {$user->name}",
            ]);
    }

    public function destroyTeacher(Teacher $teacher)
    {
        $schoolId = $this->getSchoolId();
        abort_if($teacher->user->school_id !== $schoolId, 403);

        if ($teacher->studentPkls()->whereIn('status', ['assigned', 'active'])->exists()) {
            return back()->withErrors(['teacher' => 'Guru tidak dapat dihapus karena masih memiliki alokasi siswa bimbingan aktif.']);
        }

        DB::transaction(function () use ($teacher) {
            $user = $teacher->user;
            $teacher->delete();
            if ($user) {
                $user->profile()->delete();
                $user->delete();
            }
        });

        return back()->with('success', 'Akun guru pembimbing berhasil dihapus.');
    }
}
