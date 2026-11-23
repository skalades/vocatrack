<?php

namespace App\Http\Controllers\AdminSekolah;

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
        $query = Teacher::whereHas('user', fn($q) => $q->where('school_id', $schoolId))
            ->with(['user.profile', 'studentPkls.student.user', 'majors']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $selectedMajor = $request->query('major');
        if ($selectedMajor && $selectedMajor !== 'all') {
            if ($selectedMajor === 'lintas') {
                $query->doesntHave('majors');
            } else {
                $query->whereHas('majors', fn($q) => $q->where('majors.id', $selectedMajor));
            }
        }

        $teachers = $query->latest()->paginate(15)->withQueryString();
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

        $majorPart = 'Semua_Jurusan';
        if ($major = $request->query('major')) {
            if ($major === 'lintas') {
                $majorPart = 'Lintas_Jurusan';
            } elseif ($major !== 'all') {
                $majorModel = Major::where('school_id', $schoolId)->find($major);
                if ($majorModel) {
                    $majorPart = Str::slug($majorModel->name, '_');
                }
            }
        }

        $timestamp = date('Ymd_His');
        $filename = "Akun_Akses_Guru_{$majorPart}_{$timestamp}";

        AuditLogService::log(
            'teacher_credentials_export',
            'export',
            "Mengekspor data akun dan akses guru pembimbing (Filter: {$majorPart})."
        );

        $filters = [
            'search' => $request->query('search'),
            'major' => $request->query('major'),
        ];

        // Format CSV jika secara spesifik diminta
        if ($request->query('format') === 'csv') {
            return Excel::download(
                new TeacherCredentialsExport($schoolId, $filters),
                "{$filename}.csv",
                \Maatwebsite\Excel\Excel::CSV
            );
        }

        // Default: Excel .xlsx profesional dengan multi-sheet per jurusan & styling rapi
        return Excel::download(
            new TeacherCredentialsExport($schoolId, $filters),
            "{$filename}.xlsx"
        );
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
