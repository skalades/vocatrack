<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolHoliday;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\HolidayService;
use App\Services\IncidentReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminSekolahController extends Controller
{
    protected function getSchoolId(): int
    {
        return Auth::user()->school_id;
    }

    public function dashboard()
    {
        $schoolId = $this->getSchoolId();
        $totalStudents = Student::whereHas('user', fn($q) => $q->where('school_id', $schoolId))->count();
        $totalTeachers = Teacher::whereHas('user', fn($q) => $q->where('school_id', $schoolId))->count();
        $totalCompanies = Company::where('school_id', $schoolId)->count();
        $activePkl = StudentPkl::where('school_id', $schoolId)->where('status', 'active')->count();

        $today = Carbon::today()->toDateString();
        $todayAttendances = Attendance::where('school_id', $schoolId)
            ->where('date', $today)
            ->get();

        $attendanceStats = [
            'hadir' => $todayAttendances->where('status', 'hadir')->count(),
            'terlambat' => $todayAttendances->where('status', 'terlambat')->count(),
            'izin' => $todayAttendances->where('status', 'izin')->count(),
            'sakit' => $todayAttendances->where('status', 'sakit')->count(),
            'alfa' => $todayAttendances->where('status', 'alfa')->count(),
        ];

        $recentAnnouncements = Announcement::where('school_id', $schoolId)
            ->with(['author', 'major'])
            ->pinnedFirst()
            ->limit(4)
            ->get();

        return view('adminsekolah.dashboard', compact(
            'totalStudents',
            'totalTeachers',
            'totalCompanies',
            'activePkl',
            'attendanceStats',
            'recentAnnouncements'
        ));
    }

    public function settings()
    {
        $schoolId = $this->getSchoolId();
        $school = School::findOrFail($schoolId);
        $settings = SchoolSetting::where('school_id', $schoolId)->pluck('value', 'key');
        $periods = PklPeriod::where('school_id', $schoolId)->withCount('studentPkls')->latest()->get();

        return view('adminsekolah.settings', compact('school', 'settings', 'periods'));
    }

    public function updateSettings(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $school = School::findOrFail($schoolId);

        $request->validate([
            'school_name' => ['required', 'string', 'max:150'],
            'school_phone' => ['required', 'string', 'max:20'],
            'school_address' => ['required', 'string'],
            'school_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'checkin_start' => ['required'],
            'checkin_late' => ['required'],
            'checkin_alfa' => ['required'],
            'checkout_start' => ['required'],
            'journal_backdate_limit' => ['required', 'integer', 'min:0', 'max:30'],
            'weight_dudi' => ['required', 'numeric', 'min:0', 'max:100'],
            'weight_guru' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $schoolData = [
            'name' => $request->school_name,
            'phone' => $request->school_phone,
            'address' => $request->school_address,
        ];

        if ($request->boolean('remove_logo')) {
            if ($school->logo_path && Storage::disk('public')->exists($school->logo_path)) {
                Storage::disk('public')->delete($school->logo_path);
            }
            $schoolData['logo_path'] = null;
        } elseif ($request->hasFile('school_logo')) {
            if ($school->logo_path && Storage::disk('public')->exists($school->logo_path)) {
                Storage::disk('public')->delete($school->logo_path);
            }
            $file = $request->file('school_logo');
            $filename = 'logo_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("schools/{$schoolId}/logo", $filename, 'public');
            $schoolData['logo_path'] = $path;
        }

        $school->update($schoolData);

        $settingsToUpdate = [
            'checkin_start' => $request->checkin_start,
            'checkin_late' => $request->checkin_late,
            'checkin_alfa' => $request->checkin_alfa,
            'checkout_start' => $request->checkout_start,
            'journal_backdate_limit' => $request->journal_backdate_limit,
            'weight_dudi' => $request->weight_dudi,
            'weight_guru' => $request->weight_guru,
            'kop_header_line1' => $request->kop_header_line1,
            'kop_header_line2' => $request->kop_header_line2,
            'kop_header_line3' => $request->kop_header_line3,
            'kepsek_name' => $request->kepsek_name,
            'kepsek_nip' => $request->kepsek_nip,
        ];

        foreach ($settingsToUpdate as $k => $v) {
            SchoolSetting::updateOrCreate(
                ['school_id' => $schoolId, 'key' => $k],
                ['value' => $v ?? '']
            );
        }

        return back()->with('success', 'Konfigurasi identitas sekolah dan jam kerja berhasil disimpan.');
    }

    public function majors()
    {
        $schoolId = $this->getSchoolId();
        $majors = Major::where('school_id', $schoolId)->withCount('classes')->get();
        $classes = SchoolClass::where('school_id', $schoolId)->with('major')->get();

        return view('adminsekolah.majors', compact('majors', 'classes'));
    }

    public function storeMajor(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
        ]);

        Major::create([
            'school_id' => $schoolId,
            'code' => strtoupper($request->code),
            'name' => $request->name,
        ]);

        return back()->with('success', 'Jurusan keahlian berhasil ditambahkan.');
    }

    public function storeClass(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $request->validate([
            'major_id' => ['required', Rule::exists('majors', 'id')->where('school_id', $schoolId)],
            'name' => ['required', 'string', 'max:50'],
            'grade_level' => ['required', 'in:X,XI,XII'],
            'academic_year' => ['required', 'string', 'max:15'],
        ]);

        SchoolClass::create([
            'school_id' => $schoolId,
            'major_id' => $request->major_id,
            'name' => $request->name,
            'grade_level' => $request->grade_level,
            'academic_year' => $request->academic_year,
        ]);

        return back()->with('success', 'Kelas berhasil didaftarkan.');
    }

    public function students()
    {
        $schoolId = $this->getSchoolId();
        $students = Student::whereHas('user', fn($q) => $q->where('school_id', $schoolId))
            ->with(['user.profile', 'class.major', 'activePkl.company'])
            ->latest()
            ->paginate(15);

        $classes = SchoolClass::where('school_id', $schoolId)->with('major')->get();

        return view('adminsekolah.students', compact('students', 'classes'));
    }

    public function storeStudent(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $request->validate([
            'class_id' => ['required', Rule::exists('classes', 'id')->where('school_id', $schoolId)],
            'nis' => ['required', 'string', 'max:30'],
            'nisn' => ['required', 'string', 'max:30', 'unique:students,nisn'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'gender' => ['required', 'in:L,P'],
        ]);

        DB::transaction(function () use ($request, $schoolId) {
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

        return back()->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function importStudents(Request $request)
    {
        $schoolId = $this->getSchoolId();

        $request->validate([
            'class_id' => ['required', Rule::exists('classes', 'id')->where('school_id', $schoolId)],
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        $schoolId = $this->getSchoolId();
        $classId = $request->class_id;

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
            $array = \Maatwebsite\Excel\Facades\Excel::toArray([], $file);
            if (!empty($array) && !empty($array[0])) {
                $sheet = $array[0];
                // skip header
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
                DB::transaction(function () use ($schoolId, $classId, $row) {
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

    public function teachers()
    {
        $schoolId = $this->getSchoolId();
        $teachers = Teacher::whereHas('user', fn($q) => $q->where('school_id', $schoolId))
            ->with(['user.profile', 'studentPkls.student.user'])
            ->latest()
            ->paginate(15);

        return view('adminsekolah.teachers', compact('teachers'));
    }

    public function storeTeacher(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'nip' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        DB::transaction(function () use ($request, $schoolId) {
            $user = User::create([
                'school_id' => $schoolId,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make('password'),
                'role' => 'guru-pembimbing',
                'is_active' => true,
            ]);

            Profile::create([
                'user_id' => $user->id,
                'gender' => 'L',
                'phone' => $request->phone,
            ]);

            Teacher::create([
                'user_id' => $user->id,
                'nip' => $request->nip,
            ]);
        });

        return back()->with('success', 'Akun Guru Pembimbing berhasil dibuat.');
    }

    public function companies(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $query = Company::where('school_id', $schoolId)
            ->with(['supervisors.user', 'studentPkls', 'majors']);

        // Search filter
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Major filter
        $selectedMajor = $request->query('major');
        if ($selectedMajor && $selectedMajor !== 'all') {
            if ($selectedMajor === 'lintas') {
                $query->doesntHave('majors');
            } else {
                $query->whereHas('majors', fn($q) => $q->where('majors.id', $selectedMajor));
            }
        }

        $companies = $query->latest()->paginate(10)->withQueryString();
        $majors = Major::where('school_id', $schoolId)->orderBy('name')->get();

        return view('adminsekolah.companies', compact('companies', 'majors', 'selectedMajor'));
    }

    public function storeCompany(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string'],
            'contact_person' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'work_days' => ['required', 'in:5_days,6_days'],
            'gps_enabled' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'radius_meters' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'major_ids' => ['nullable', 'array'],
            'major_ids.*' => [Rule::exists('majors', 'id')->where('school_id', $schoolId)],
            // Optional initial supervisor
            'supervisor_name' => ['nullable', 'string', 'max:100'],
            'supervisor_email' => ['nullable', 'email', 'unique:users,email'],
            'supervisor_position' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($request, $schoolId) {
            $company = Company::create([
                'school_id' => $schoolId,
                'name' => $request->name,
                'address' => $request->address,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'work_days' => $request->work_days,
                'gps_enabled' => $request->boolean('gps_enabled'),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'radius_meters' => $request->radius_meters ?? 100,
            ]);

            if ($request->has('major_ids')) {
                $company->majors()->sync($request->major_ids);
            }

            if ($request->filled('supervisor_email')) {
                $user = User::create([
                    'school_id' => $schoolId,
                    'name' => $request->supervisor_name ?? $request->contact_person,
                    'email' => $request->supervisor_email,
                    'password' => Hash::make('password'),
                    'role' => 'pembimbing-dudi',
                    'is_active' => true,
                ]);

                Profile::create([
                    'user_id' => $user->id,
                    'gender' => 'L',
                ]);

                Supervisor::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'position' => $request->supervisor_position ?? 'Mentor Lapangan DU/DI',
                ]);
            }
        });

        return back()->with('success', 'Data Mitra DU/DI berhasil ditambahkan.');
    }

    public function updateCompany(Request $request, Company $company)
    {
        $schoolId = $this->getSchoolId();
        abort_if($company->school_id !== $schoolId, 403);

        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string'],
            'contact_person' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'work_days' => ['required', 'in:5_days,6_days'],
            'gps_enabled' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'radius_meters' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'major_ids' => ['nullable', 'array'],
            'major_ids.*' => [Rule::exists('majors', 'id')->where('school_id', $schoolId)],
        ]);

        DB::transaction(function () use ($request, $company) {
            $company->update([
                'name' => $request->name,
                'address' => $request->address,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'work_days' => $request->work_days,
                'gps_enabled' => $request->boolean('gps_enabled'),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'radius_meters' => $request->radius_meters ?? 100,
            ]);

            $company->majors()->sync($request->major_ids ?? []);
        });

        return back()->with('success', 'Data Mitra DU/DI berhasil diperbarui.');
    }

    public function plotting(Request $request)
    {
        $schoolId = $this->getSchoolId();

        // 1. Periods
        $periods = PklPeriod::where('school_id', $schoolId)->withCount('studentPkls')->latest()->get();
        $activePeriod = $periods->firstWhere('is_active', true) ?? $periods->first();
        $selectedPeriodId = $request->query('period_id', $activePeriod?->id);

        // 2. Query StudentPkls (Filter & Search)
        $query = StudentPkl::where('school_id', $schoolId)
            ->with(['student.user', 'student.class.major', 'company.majors', 'teacher.user', 'supervisor.user', 'pklPeriod']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('student.user', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('student', fn($sq) => $sq->where('nisn', 'like', "%{$search}%"))
                  ->orWhereHas('company', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($majorId = $request->query('major_id')) {
            if ($majorId !== 'all') {
                $query->whereHas('student.class', fn($cq) => $cq->where('major_id', $majorId));
            }
        }

        if ($periodFilter = $request->query('period')) {
            if ($periodFilter !== 'all') {
                $query->where('pkl_period_id', $periodFilter);
            }
        }

        if ($teacherId = $request->query('teacher_id')) {
            if ($teacherId !== 'all') {
                $query->where('teacher_id', $teacherId);
            }
        }

        $studentPkls = $query->latest()->paginate(15)->withQueryString();

        // 3. All students with plotting status for active period
        $allStudents = Student::whereHas('user', fn($q) => $q->where('school_id', $schoolId))
            ->with(['user', 'class.major'])
            ->get();

        $plottedMap = [];
        if ($selectedPeriodId) {
            $plottedRecords = StudentPkl::where('school_id', $schoolId)
                ->where('pkl_period_id', $selectedPeriodId)
                ->with('company')
                ->get();
            foreach ($plottedRecords as $pr) {
                $plottedMap[$pr->student_id] = $pr->company->name ?? 'DU/DI';
            }
        }

        $students = $allStudents->map(function ($st) use ($plottedMap) {
            $st->is_plotted = isset($plottedMap[$st->id]);
            $st->plotted_company = $st->is_plotted ? $plottedMap[$st->id] : null;
            return $st;
        });

        // 4. Other master data
        $companies = Company::where('school_id', $schoolId)->with(['supervisors.user', 'majors'])->get();
        $teachers = Teacher::whereHas('user', fn($q) => $q->where('school_id', $schoolId))->with('user')->get();
        $majors = Major::where('school_id', $schoolId)->with('classes')->orderBy('name')->get();
        $classes = SchoolClass::where('school_id', $schoolId)->with('major')->orderBy('name')->get();

        // 5. KPI Stats
        $totalStudents = $allStudents->count();
        $totalPlotted = count($plottedMap);
        $totalUnplotted = max(0, $totalStudents - $totalPlotted);
        $totalCompanies = $companies->count();

        return view('adminsekolah.plotting', compact(
            'studentPkls',
            'students',
            'companies',
            'periods',
            'teachers',
            'majors',
            'classes',
            'totalStudents',
            'totalPlotted',
            'totalUnplotted',
            'totalCompanies'
        ));
    }

    public function storePlotting(Request $request)
    {
        $schoolId = $this->getSchoolId();

        $request->validate([
            'student_id' => [
                'required_without:student_ids',
                'nullable',
                Rule::exists('students', 'id')->where(function ($query) use ($schoolId) {
                    $query->whereIn('user_id', User::where('school_id', $schoolId)->select('id'));
                })
            ],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => [
                Rule::exists('students', 'id')->where(function ($query) use ($schoolId) {
                    $query->whereIn('user_id', User::where('school_id', $schoolId)->select('id'));
                })
            ],
            'company_id' => [
                'required',
                Rule::exists('companies', 'id')->where('school_id', $schoolId)
            ],
            'pkl_period_id' => [
                'required',
                Rule::exists('pkl_periods', 'id')->where('school_id', $schoolId)
            ],
            'teacher_id' => [
                'required',
                Rule::exists('teachers', 'id')->where(function ($query) use ($schoolId) {
                    $query->whereIn('user_id', User::where('school_id', $schoolId)->select('id'));
                })
            ],
            'supervisor_id' => [
                'nullable',
                Rule::exists('supervisors', 'id')->where(function ($query) use ($schoolId) {
                    $query->whereIn('user_id', User::where('school_id', $schoolId)->select('id'));
                })
            ],
        ]);

        $studentIds = $request->has('student_ids') && is_array($request->student_ids) && count($request->student_ids) > 0
            ? array_unique(array_filter($request->student_ids))
            : ($request->filled('student_id') ? [$request->student_id] : []);

        if (empty($studentIds)) {
            return back()->withErrors(['student_id' => 'Pilih setidaknya satu siswa untuk diplot.']);
        }

        // Concurrency-safe plotting with lock and duplicate key catch
        return DB::transaction(function () use ($schoolId, $request, $studentIds) {
            $alreadyPlotted = StudentPkl::where('school_id', $schoolId)
                ->where('pkl_period_id', $request->pkl_period_id)
                ->whereIn('student_id', $studentIds)
                ->lockForUpdate()
                ->pluck('student_id')
                ->toArray();

            if (!empty($alreadyPlotted)) {
                if (count($studentIds) === 1) {
                    return back()->withErrors(['student_id' => 'Siswa sudah diplot ke DU/DI pada periode PKL yang sama.']);
                }
                return back()->withErrors(['student_id' => count($alreadyPlotted) . ' siswa yang dipilih sudah memiliki penugasan DU/DI di periode ini.']);
            }

            try {
                foreach ($studentIds as $sId) {
                    StudentPkl::create([
                        'school_id' => $schoolId,
                        'student_id' => $sId,
                        'company_id' => $request->company_id,
                        'pkl_period_id' => $request->pkl_period_id,
                        'teacher_id' => $request->teacher_id,
                        'supervisor_id' => $request->supervisor_id,
                        'status' => 'active',
                    ]);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                if (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Integrity constraint violation')) {
                    return back()->withErrors(['student_id' => 'Siswa sudah diplot ke DU/DI pada periode PKL yang sama.']);
                }
                throw $e;
            }

            $count = count($studentIds);
            return back()->with('success', "Penugasan PKL untuk {$count} siswa berhasil disimpan.");
        });
    }

    public function storePeriod(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $period = PklPeriod::create([
            'school_id' => $schoolId,
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Periode PKL '{$period->name}' berhasil ditambahkan.");
    }

    public function updatePeriod(Request $request, PklPeriod $period)
    {
        $schoolId = $this->getSchoolId();
        abort_if($period->school_id !== $schoolId, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $period->update([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', "Periode PKL '{$period->name}' berhasil diperbarui.");
    }

    public function togglePeriodActive(PklPeriod $period)
    {
        $schoolId = $this->getSchoolId();
        abort_if($period->school_id !== $schoolId, 403);

        $period->update([
            'is_active' => !$period->is_active,
        ]);

        $status = $period->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Periode PKL '{$period->name}' berhasil {$status}.");
    }

    public function holidays(Request $request, HolidayService $holidayService)
    {
        $schoolId = $this->getSchoolId();
        $selectedYear = (int) $request->input('year', Carbon::now()->year);
        $selectedMonth = $request->filled('month') ? (int) $request->month : null;

        $holidays = $holidayService->getHolidays($schoolId, $selectedYear, $selectedMonth);

        $stats = [
            'total' => SchoolHoliday::withoutGlobalScopes()->where('school_id', $schoolId)->whereYear('holiday_date', $selectedYear)->count(),
            'national' => SchoolHoliday::withoutGlobalScopes()->where('school_id', $schoolId)->whereYear('holiday_date', $selectedYear)->where('is_national', true)->count(),
            'custom' => SchoolHoliday::withoutGlobalScopes()->where('school_id', $schoolId)->whereYear('holiday_date', $selectedYear)->where('is_national', false)->count(),
        ];

        return view('adminsekolah.holidays', compact('holidays', 'selectedYear', 'selectedMonth', 'stats'));
    }

    public function storeHoliday(Request $request, HolidayService $holidayService)
    {
        $schoolId = $this->getSchoolId();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'holiday_date' => [
                'required',
                'date',
                Rule::unique('school_holidays')->where(fn($q) => $q->where('school_id', $schoolId)),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_national' => ['nullable', 'boolean'],
        ], [
            'holiday_date.unique' => 'Tanggal ini sudah terdaftar sebagai hari libur.',
        ]);

        $holidayService->createHoliday($schoolId, $validated);

        return back()->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function destroyHoliday(SchoolHoliday $holiday, HolidayService $holidayService)
    {
        $schoolId = $this->getSchoolId();
        abort_if($holiday->school_id !== $schoolId, 403);

        $holidayService->deleteHoliday($holiday);

        return back()->with('success', 'Hari libur berhasil dihapus.');
    }

    public function syncNationalHolidays(Request $request, HolidayService $holidayService)
    {
        $schoolId = $this->getSchoolId();
        $year = (int) $request->input('year', Carbon::now()->year);

        $count = $holidayService->syncNationalHolidays($schoolId, $year);

        return back()->with('success', "Berhasil mensinkronkan {$count} hari libur nasional untuk tahun {$year}.");
    }

    public function incidents(Request $request, IncidentReportService $incidentService)
    {
        $schoolId = $this->getSchoolId();
        $incidents = $incidentService->getIncidentsForSchool($schoolId, $request->only(['status', 'urgency']));

        $stats = [
            'total' => IncidentReport::where('school_id', $schoolId)->count(),
            'open' => IncidentReport::where('school_id', $schoolId)->whereIn('status', ['submitted', 'in_progress'])->count(),
            'critical' => IncidentReport::where('school_id', $schoolId)->where('urgency', 'critical')->count(),
            'resolved' => IncidentReport::where('school_id', $schoolId)->where('status', 'resolved')->count(),
        ];

        return view('adminsekolah.incidents', compact('incidents', 'stats'));
    }

    public function followupIncident(Request $request, IncidentReport $report, IncidentReportService $incidentService)
    {
        $schoolId = $this->getSchoolId();
        abort_if($report->school_id !== $schoolId, 403);

        $validated = $request->validate([
            'action_note' => ['required', 'string', 'max:1000'],
            'status' => ['nullable', 'in:in_progress,resolved,dismissed'],
        ]);

        $incidentService->addFollowup($report, Auth::user(), $validated['action_note'], $validated['status'] ?? null);

        return back()->with('success', 'Tindak lanjut laporan kendala berhasil dicatat.');
    }

    public function certificates(Request $request, CertificateService $certificateService)
    {
        $schoolId = $this->getSchoolId();
        $certificates = $certificateService->getCertificatesForSchool($schoolId, $request->only('search'));

        $unissuedStudents = StudentPkl::where('school_id', $schoolId)
            ->whereHas('assessment')
            ->doesntHave('certificate')
            ->with(['student.user', 'company', 'assessment'])
            ->get();

        $periods = PklPeriod::where('school_id', $schoolId)->get();

        $stats = [
            'total_issued' => Certificate::where('school_id', $schoolId)->count(),
            'ready_to_issue' => $unissuedStudents->count(),
            'sangat_baik' => Certificate::where('school_id', $schoolId)->where('final_predicate', 'Sangat Baik')->count(),
        ];

        return view('adminsekolah.certificates', compact('certificates', 'unissuedStudents', 'periods', 'stats'));
    }

    public function generateCertificate(Request $request, CertificateService $certificateService)
    {
        $schoolId = $this->getSchoolId();
        $validated = $request->validate([
            'student_pkl_id' => ['required', 'exists:student_pkl,id'],
        ]);

        $spkl = StudentPkl::where('school_id', $schoolId)->findOrFail($validated['student_pkl_id']);
        $certificate = $certificateService->generateForStudentPkl($spkl);

        return back()->with('success', "E-Sertifikat untuk {$spkl->student->user->name} berhasil diterbitkan (No: {$certificate->certificate_number}).");
    }

    public function generateBatchCertificates(Request $request, CertificateService $certificateService)
    {
        $schoolId = $this->getSchoolId();
        $periodId = $request->input('pkl_period_id') ? (int) $request->input('pkl_period_id') : null;

        $count = $certificateService->generateBatchForSchool($schoolId, $periodId);

        return back()->with('success', "Berhasil menerbitkan {$count} E-Sertifikat baru secara massal.");
    }

    public function downloadCertificate(Certificate $certificate, CertificateService $certificateService)
    {
        $schoolId = $this->getSchoolId();
        abort_if($certificate->school_id !== $schoolId, 403, 'Akses ditolak.');

        $pdf = $certificateService->renderPdf($certificate);
        $nameSlug = Str::slug($certificate->studentPkl->student->user->name ?? 'Siswa');
        $safeCertNumber = str_replace(['/', '\\'], '-', $certificate->certificate_number);

        return $pdf->download("E-Sertifikat_PKL_{$nameSlug}_{$safeCertNumber}.pdf");
    }
}
