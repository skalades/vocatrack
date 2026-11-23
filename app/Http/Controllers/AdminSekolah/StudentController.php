<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Exports\StudentCredentialsExport;
use App\Http\Requests\AdminSekolah\StoreStudentRequest;
use App\Http\Requests\AdminSekolah\UpdateStudentRequest;
use App\Models\Company;
use App\Models\Major;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPasswordResetRequest;
use App\Models\StudentPkl;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\SubscriptionService;
use App\Services\TenantProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends BaseAdminSekolahController
{
    public function students(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $query = Student::whereHas('user', fn($q) => $q->where('school_id', $schoolId))
            ->with(['user.profile', 'class.major', 'activePkl.company']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhereHas('class', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($majorId = $request->query('major_id')) {
            if ($majorId !== 'all') {
                $query->whereHas('class', fn($cq) => $cq->where('major_id', $majorId));
            }
        }

        if ($classId = $request->query('class_id')) {
            if ($classId !== 'all') {
                $query->where('class_id', $classId);
            }
        }

        if ($companyId = $request->query('company_id')) {
            if ($companyId !== 'all') {
                if ($companyId === 'unassigned') {
                    $query->whereDoesntHave('activePkl');
                } else {
                    $query->whereHas('activePkl', fn($pq) => $pq->where('company_id', $companyId));
                }
            }
        }

        $students = $query->latest()->paginate(20)->withQueryString();
        $classes = SchoolClass::where('school_id', $schoolId)->with('major')->get();
        $majors = Major::where('school_id', $schoolId)->orderBy('name')->get();
        $companies = Company::where('school_id', $schoolId)->orderBy('name')->get();

        // Exact DB metrics across the entire school (Scalable)
        $totalStudents = Student::whereHas('user', fn($q) => $q->where('school_id', $schoolId))->count();
        $totalPlotted = StudentPkl::where('school_id', $schoolId)->whereIn('status', ['assigned', 'active'])->distinct('student_id')->count('student_id');
        $totalUnplotted = max(0, $totalStudents - $totalPlotted);

        return view('adminsekolah.students', compact('students', 'classes', 'majors', 'companies', 'totalStudents', 'totalPlotted', 'totalUnplotted'));
    }

    public function exportStudents(Request $request)
    {
        $schoolId = $this->getSchoolId();

        $majorPart = 'Semua_Jurusan';
        if ($majorId = $request->query('major_id')) {
            if ($majorId !== 'all') {
                $major = Major::where('school_id', $schoolId)->find($majorId);
                if ($major) {
                    $majorPart = Str::slug($major->name, '_');
                }
            }
        }

        $classPart = 'Semua_Kelas';
        if ($classId = $request->query('class_id')) {
            if ($classId !== 'all') {
                $class = SchoolClass::where('school_id', $schoolId)->find($classId);
                if ($class) {
                    $classPart = Str::slug($class->name, '_');
                }
            }
        }

        $companyPart = '';
        if ($companyId = $request->query('company_id')) {
            if ($companyId !== 'all') {
                if ($companyId === 'unassigned') {
                    $companyPart = '_Belum_Ditempatkan';
                } else {
                    $company = Company::where('school_id', $schoolId)->find($companyId);
                    if ($company) {
                        $companyPart = '_' . Str::slug($company->name, '_');
                    }
                }
            }
        }

        $timestamp = date('Ymd_His');
        $filename = "Akun_Akses_Siswa_{$majorPart}_{$classPart}{$companyPart}_{$timestamp}";

        AuditLogService::log(
            'student_credentials_export',
            'export',
            "Mengekspor data akun dan akses siswa (Filter: {$majorPart}, {$classPart}{$companyPart})."
        );

        $filters = [
            'search' => $request->query('search'),
            'major_id' => $request->query('major_id'),
            'class_id' => $request->query('class_id'),
            'company_id' => $request->query('company_id'),
        ];

        // Format CSV jika secara spesifik diminta
        if ($request->query('format') === 'csv') {
            return Excel::download(
                new StudentCredentialsExport($schoolId, $filters),
                "{$filename}.csv",
                \Maatwebsite\Excel\Excel::CSV
            );
        }

        // Default: Excel .xlsx profesional dengan multi-sheet & styling rapi
        return Excel::download(
            new StudentCredentialsExport($schoolId, $filters),
            "{$filename}.xlsx"
        );
    }

    public function storeStudent(StoreStudentRequest $request)
    {
        $schoolId = $this->getSchoolId();
        $school = School::findOrFail($schoolId);

        try {
            DB::transaction(function () use ($request, $schoolId, $school) {
                // Atomic quota enforcement with row locking to eliminate TOCTOU race conditions
                app(SubscriptionService::class)->enforceQuotaWithLock($school, 1);

                $user = User::create([
                    'school_id' => $schoolId,
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make('password'),
                    'role' => 'siswa',
                    'is_active' => true,
                ]);

                Profile::create([
                    'user_id' => $user->id,
                    'gender' => $request->gender,
                    'phone' => $request->phone,
                ]);

                Student::create([
                    'user_id' => $user->id,
                    'class_id' => $request->class_id,
                    'nis' => $request->nis,
                    'nisn' => $request->nisn,
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = $e->validator->errors()->first('quota') ?: $e->getMessage();
            return back()->with('error', $msg)->withInput();
        }

        return back()->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function updateStudent(UpdateStudentRequest $request, Student $student)
    {
        $schoolId = $this->getSchoolId();
        abort_if($student->user->school_id !== $schoolId, 403);

        DB::transaction(function () use ($request, $student) {
            $student->user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            $student->user->profile()->updateOrCreate(
                ['user_id' => $student->user_id],
                [
                    'gender' => $request->gender,
                    'phone' => $request->phone,
                ]
            );

            $student->update([
                'class_id' => $request->class_id,
                'nis' => $request->nis,
                'nisn' => $request->nisn,
            ]);
        });

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function resetStudentPassword(Request $request, Student $student)
    {
        $schoolId = $this->getSchoolId();
        abort_if($student->user->school_id !== $schoolId, 403);

        $validated = $request->validate([
            'new_password' => ['nullable', 'string', 'min:8', 'max:100'],
        ], [
            'new_password.min' => 'Kata sandi baru minimal 8 karakter. Kosongkan untuk dibuatkan otomatis.',
        ]);

        $user = $student->user;
        $newPassword = $validated['new_password'] ?? TenantProvisioningService::generateTemporaryPassword();

        DB::transaction(function () use ($user, $newPassword, $student, $schoolId) {
            $user->update([
                'password' => $newPassword,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }

            StudentPasswordResetRequest::where('student_id', $student->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'completed',
                    'notes' => "Direset oleh Admin Sekolah " . Auth::user()->name . " pada " . now()->format('d/m/Y H:i'),
                ]);

            AuditLogService::log(
                'password_reset',
                'security',
                "Kata sandi akun siswa ({$user->email}) direset oleh Admin Sekolah " . Auth::user()->email . '.',
                null,
                Auth::user(),
                null,
                ['target_user_id' => $user->id, 'student_id' => $student->id, 'school_id' => $schoolId]
            );
        });

        return back()
            ->with('success', "Kata sandi siswa {$user->name} berhasil direset. Siswa wajib menggantinya saat login berikutnya.")
            ->with('temporary_credential', [
                'email' => $user->email,
                'password' => $newPassword,
                'context' => "Siswa {$user->name}",
            ]);
    }

    public function destroyStudent(Student $student)
    {
        $schoolId = $this->getSchoolId();
        abort_if($student->user->school_id !== $schoolId, 403);

        if ($student->studentPkls()->exists()) {
            return back()->withErrors(['student' => 'Siswa tidak dapat dihapus karena sudah memiliki riwayat penugasan PKL.']);
        }

        DB::transaction(function () use ($student) {
            $user = $student->user;
            $student->delete();
            if ($user) {
                $user->profile()->delete();
                $user->delete();
            }
        });

        return back()->with('success', 'Data siswa dan akun login berhasil dihapus.');
    }

    public function importStudents(Request $request)
    {
        $schoolId = $this->getSchoolId();

        $request->validate([
            'class_id' => ['required', Rule::exists('classes', 'id')->where('school_id', $schoolId)],
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        $rows = [];
        if (in_array($extension, ['csv', 'txt'])) {
            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle, 1000, ',');
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($data) >= 5) {
                    $rows[] = [
                        'nis' => trim($data[0]),
                        'nisn' => trim($data[1]),
                        'name' => trim($data[2]),
                        'email' => trim($data[3]),
                        'gender' => strtoupper(trim($data[4])),
                    ];
                }
            }
            fclose($handle);
        } else {
            // Read Excel with Maatwebsite Excel
            $array = Excel::toArray([], $file);
            if (!empty($array) && !empty($array[0])) {
                $sheet = $array[0];
                for ($i = 1; $i < count($sheet); $i++) {
                    $r = $sheet[$i];
                    if (isset($r[0], $r[1], $r[2], $r[3])) {
                        $rows[] = [
                            'nis' => (string) $r[0],
                            'nisn' => (string) $r[1],
                            'name' => (string) $r[2],
                            'email' => (string) $r[3],
                            'gender' => isset($r[4]) ? strtoupper((string) $r[4]) : 'L',
                        ];
                    }
                }
            }
        }

        $school = School::find($schoolId);
        $quotaCheck = app(SubscriptionService::class)->canAddStudent($school, count($rows));
        if (!$quotaCheck['allowed']) {
            return back()->with('error', $quotaCheck['message']);
        }

        $classId = $request->class_id;
        $imported = 0;
        $failed = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            if (empty($row['nisn']) || empty($row['email'])) {
                $failed[] = "Baris {$rowNum}: NISN atau Email kosong.";
                continue;
            }

            if (Student::where('nisn', $row['nisn'])->exists()) {
                $failed[] = "Baris {$rowNum}: Duplikasi NISN '{$row['nisn']}'.";
                continue;
            }

            if (User::where('email', $row['email'])->exists()) {
                $failed[] = "Baris {$rowNum}: Duplikasi Email '{$row['email']}'.";
                continue;
            }

            try {
                DB::transaction(function () use ($school, $schoolId, $classId, $row) {
                    // Atomic quota enforcement with row lock per student insertion
                    app(SubscriptionService::class)->enforceQuotaWithLock($school, 1);

                    $user = User::create([
                        'school_id' => $schoolId,
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'password' => Hash::make('password'),
                        'role' => 'siswa',
                        'is_active' => true,
                    ]);

                    Profile::create([
                        'user_id' => $user->id,
                        'gender' => in_array($row['gender'], ['L', 'P']) ? $row['gender'] : 'L',
                    ]);

                    Student::create([
                        'user_id' => $user->id,
                        'class_id' => $classId,
                        'nis' => $row['nis'],
                        'nisn' => $row['nisn'],
                    ]);
                });
                $imported++;
            } catch (\Illuminate\Validation\ValidationException $e) {
                $failed[] = "Baris {$rowNum}: " . ($e->validator->errors()->first('quota') ?: $e->getMessage());
            } catch (\Exception $e) {
                $failed[] = "Baris {$rowNum}: " . $e->getMessage();
            }
        }

        $msg = "Impor selesai: {$imported} siswa berhasil diimpor.";
        if (!empty($failed)) {
            $msg .= " " . count($failed) . " baris gagal (lihat log di bawah).";
            return back()->with('success', $msg)->with('failedRows', $failed);
        }

        return back()->with('success', $msg);
    }
}
