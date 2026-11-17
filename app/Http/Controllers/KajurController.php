<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\Journal;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\Profile;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\SupervisionVisit;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class KajurController extends Controller
{
    protected function getSchoolId(): int
    {
        return (int) Auth::user()->school_id;
    }

    protected function getHeadedMajor(): Major
    {
        $major = Auth::user()->headedMajor();
        abort_if(!$major || $major->school_id !== $this->getSchoolId(), 403, 'Akses ditolak: Anda belum terdaftar sebagai Kepala Program Keahlian yang valid.');

        return $major;
    }

    /**
     * Dashboard analitik & ringkasan operasional jurusan.
     */
    public function dashboard()
    {
        $schoolId = $this->getSchoolId();
        $major = $this->getHeadedMajor();

        $classIds = $major->classes()->pluck('id');
        $studentIds = Student::whereIn('class_id', $classIds)->pluck('id');

        $totalStudents = $studentIds->count();
        $totalPlotted = StudentPkl::where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->where('status', 'active')
            ->count();
        $totalUnplotted = max(0, $totalStudents - $totalPlotted);

        $totalCompanies = $major->companies()->count();

        // Absensi siswa jurusan hari ini
        $today = Carbon::today()->toDateString();
        $todayAttendances = Attendance::where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->whereDate('date', $today)
            ->get();

        $hadirToday = $todayAttendances->whereIn('status', ['hadir', 'terlambat'])->count();
        $izinToday = $todayAttendances->whereIn('status', ['izin', 'sakit', 'dinas_luar', 'luar_kantor'])->count();
        $alfaToday = $todayAttendances->where('status', 'alfa')->count();

        // Jurnal pending review guru untuk siswa jurusan
        $pendingJournalsCount = Journal::where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->where('status', 'pending_teacher')
            ->count();

        // Siswa bimbingan pribadi (jika Kajur juga bertindak sebagai Guru Pembimbing)
        $myTeacherId = Auth::user()->teacher?->id;
        $myMenteesCount = $myTeacherId 
            ? StudentPkl::where('school_id', $schoolId)->where('teacher_id', $myTeacherId)->where('status', 'active')->count()
            : 0;
        $myPendingJournalsCount = $myTeacherId
            ? Journal::where('school_id', $schoolId)->whereHas('studentPkl', fn($q) => $q->where('teacher_id', $myTeacherId))->where('status', 'pending_teacher')->count()
            : 0;

        // Insiden kendala kerja terbaru pada siswa jurusan
        $recentIncidents = IncidentReport::where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->with(['student.user', 'company'])
            ->latest()
            ->take(5)
            ->get();

        return view('kajur.dashboard', compact(
            'major',
            'totalStudents',
            'totalPlotted',
            'totalUnplotted',
            'totalCompanies',
            'hadirToday',
            'izinToday',
            'alfaToday',
            'pendingJournalsCount',
            'myMenteesCount',
            'myPendingJournalsCount',
            'recentIncidents'
        ));
    }

    /**
     * Daftar mitra perusahaan (DU/DI) yang terhubung ke jurusan ini.
     */
    public function companies(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $major = $this->getHeadedMajor();

        $query = $major->companies()
            ->withCount([
                'studentPkls as active_students_count' => fn($q) => $q->where('school_id', $schoolId)->where('status', 'active'),
            ])
            ->with('supervisors.user');

        if ($request->filled('search')) {
            $search = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('contact_person', 'like', $search)
                  ->orWhere('address', 'like', $search);
            });
        }

        $companies = $query->latest('companies.created_at')->paginate(12)->withQueryString();

        return view('kajur.companies', compact('major', 'companies'));
    }

    /**
     * Tambah mitra perusahaan oleh Kepala Jurusan.
     */
    public function storeCompany(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $major = $this->getHeadedMajor();

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
            // Akun pembimbing DU/DI opsional
            'supervisor_name' => ['nullable', 'string', 'max:100'],
            'supervisor_email' => ['nullable', 'email', 'unique:users,email'],
            'supervisor_phone' => ['nullable', 'string', 'max:20'],
            'supervisor_position' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($request, $schoolId, $major) {
            $company = Company::create([
                'school_id' => $schoolId,
                'created_by_user_id' => Auth::id(),
                'name' => $request->name,
                'address' => $request->address,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'work_days' => $request->work_days,
                'gps_enabled' => (bool) $request->gps_enabled,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'radius_meters' => $request->radius_meters ?: 100,
            ]);

            // Hubungkan perusahaan ke jurusan ini secara otomatis
            $company->majors()->syncWithoutDetaching([$major->id]);

            // Buat akun pembimbing DU/DI jika diisi
            if ($request->filled('supervisor_name') && $request->filled('supervisor_email')) {
                $user = User::create([
                    'school_id' => $schoolId,
                    'name' => $request->supervisor_name,
                    'email' => $request->supervisor_email,
                    'password' => Hash::make('password'),
                    'role' => 'pembimbing-dudi',
                    'is_active' => true,
                ]);

                Profile::create([
                    'user_id' => $user->id,
                    'gender' => 'L',
                    'phone' => $request->supervisor_phone,
                ]);

                Supervisor::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'position' => $request->supervisor_position ?: 'Pembimbing Lapangan',
                ]);
            }
        });

        return back()->with('success', "Mitra perusahaan '{$request->name}' berhasil ditambahkan dan ditautkan ke jurusan {$major->name}.");
    }

    /**
     * Perbarui data mitra perusahaan oleh Kepala Jurusan.
     */
    public function updateCompany(Request $request, Company $company)
    {
        $schoolId = $this->getSchoolId();
        $major = $this->getHeadedMajor();

        abort_if($company->school_id !== $schoolId, 403);
        abort_if(!$company->majors()->where('majors.id', $major->id)->exists(), 403, 'Perusahaan ini tidak terhubung dengan jurusan Anda.');

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
        ]);

        $company->update([
            'name' => $request->name,
            'address' => $request->address,
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'work_days' => $request->work_days,
            'gps_enabled' => (bool) $request->gps_enabled,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius_meters' => $request->radius_meters ?: 100,
        ]);

        return back()->with('success', "Data mitra perusahaan '{$company->name}' berhasil diperbarui.");
    }

    /**
     * Hapus / putus relasi mitra perusahaan oleh Kepala Jurusan.
     */
    public function destroyCompany(Company $company)
    {
        $schoolId = $this->getSchoolId();
        $major = $this->getHeadedMajor();

        abort_if($company->school_id !== $schoolId, 403);
        abort_if(!$company->majors()->where('majors.id', $major->id)->exists(), 403);

        $hasAssignedStudents = StudentPkl::where('school_id', $schoolId)
            ->where('company_id', $company->id)
            ->exists();

        if ($hasAssignedStudents) {
            return back()->with('error', 'Tidak dapat menghapus mitra ini karena masih ada siswa yang terplotting.');
        }

        // Lepas kaitan dari jurusan ini
        $company->majors()->detach($major->id);

        // Jika tidak terkait ke jurusan lain dan dibuat oleh Kajur ini, hapus permanen
        if ($company->majors()->count() === 0 && $company->created_by_user_id === Auth::id()) {
            $company->delete();
        }

        return back()->with('success', "Mitra perusahaan berhasil dilepas dari jurusan {$major->name}.");
    }

    /**
     * Daftar siswa & plotting penugasan PKL khusus jurusan ini.
     */
    public function students(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $major = $this->getHeadedMajor();

        $classes = $major->classes()->orderBy('name')->get();
        $classIds = $classes->pluck('id');

        $query = Student::whereIn('class_id', $classIds)
            ->with(['user', 'schoolClass', 'studentPkl.company', 'studentPkl.teacher.user']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'plotted') {
                $query->whereHas('studentPkl', fn($q) => $q->where('status', 'active'));
            } elseif ($request->status === 'unplotted') {
                $query->whereDoesntHave('studentPkl', fn($q) => $q->where('status', 'active'));
            }
        }

        if ($request->filled('search')) {
            $search = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('nisn', 'like', $search)
                  ->orWhere('nis', 'like', $search);
            });
        }

        $students = $query->orderBy('name')->paginate(20)->withQueryString();

        // Data pendukung untuk modal plotting
        $companies = $major->companies()->orderBy('name')->get();
        $teachers = Teacher::whereHas('user', fn($q) => $q->where('school_id', $schoolId)->where('is_active', true))
            ->with('user')
            ->withCount(['studentPkls as active_mentees_count' => fn($q) => $q->where('status', 'active')])
            ->get();

        $myTeacherId = Auth::user()->teacher?->id;

        return view('kajur.students', compact(
            'major',
            'classes',
            'students',
            'companies',
            'teachers',
            'myTeacherId'
        ));
    }

    /**
     * Simpan / ubah plotting penugasan PKL siswa oleh Kepala Jurusan.
     */
    public function assignPlotting(Request $request, Student $student)
    {
        $schoolId = $this->getSchoolId();
        $major = $this->getHeadedMajor();

        abort_if(!$major->classes()->where('id', $student->class_id)->exists(), 403, 'Siswa bukan berasal dari jurusan Anda.');

        $request->validate([
            'company_id' => [
                'required',
                Rule::exists('companies', 'id')->where('school_id', $schoolId),
            ],
            'teacher_id' => [
                'required',
                Rule::exists('teachers', 'id')->where(fn($q) => $q->whereIn('user_id', User::where('school_id', $schoolId)->pluck('id'))),
            ],
        ]);

        $activePeriod = PklPeriod::where('school_id', $schoolId)->where('is_active', true)->first();

        StudentPkl::updateOrCreate(
            [
                'school_id' => $schoolId,
                'student_id' => $student->id,
            ],
            [
                'company_id' => $request->company_id,
                'teacher_id' => $request->teacher_id,
                'pkl_period_id' => $activePeriod?->id,
                'status' => 'active',
            ]
        );

        return back()->with('success', "Penugasan PKL untuk siswa {$student->name} berhasil diperbarui.");
    }

    /**
     * Monitoring presensi harian seluruh siswa di jurusannya.
     */
    public function attendance(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $major = $this->getHeadedMajor();

        $classes = $major->classes()->orderBy('name')->get();
        $classIds = $classes->pluck('id');
        $studentIds = Student::whereIn('class_id', $classIds)->pluck('id');

        $selectedDate = $request->input('date', Carbon::today()->toDateString());

        $attendancesQuery = Attendance::where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->whereDate('date', $selectedDate)
            ->with(['student.schoolClass', 'student.studentPkl.company', 'student.studentPkl.teacher.user']);

        if ($request->filled('class_id')) {
            $attendancesQuery->whereHas('student', fn($q) => $q->where('class_id', $request->class_id));
        }

        if ($request->filled('status')) {
            $attendancesQuery->where('status', $request->status);
        }

        $attendances = $attendancesQuery->latest('created_at')->paginate(25)->withQueryString();

        $stats = [
            'total' => (clone $attendancesQuery)->count(),
            'hadir' => (clone $attendancesQuery)->where('status', 'hadir')->count(),
            'terlambat' => (clone $attendancesQuery)->where('status', 'terlambat')->count(),
            'izin' => (clone $attendancesQuery)->whereIn('status', ['izin', 'sakit', 'dinas_luar', 'luar_kantor'])->count(),
            'alfa' => (clone $attendancesQuery)->where('status', 'alfa')->count(),
        ];

        return view('kajur.attendance', compact(
            'major',
            'classes',
            'attendances',
            'selectedDate',
            'stats'
        ));
    }

    /**
     * Monitoring & review jurnal kerja harian siswa di jurusannya.
     */
    public function journals(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $major = $this->getHeadedMajor();

        $classes = $major->classes()->orderBy('name')->get();
        $classIds = $classes->pluck('id');
        $studentIds = Student::whereIn('class_id', $classIds)->pluck('id');

        $query = Journal::where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->with(['student.schoolClass', 'student.studentPkl.teacher.user', 'photos']);

        if ($request->filled('class_id')) {
            $query->whereHas('student', fn($q) => $q->where('class_id', $request->class_id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $journals = $query->latest('date')->paginate(20)->withQueryString();

        $pendingCount = Journal::where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->where('status', 'pending_teacher')
            ->count();

        return view('kajur.journals', compact(
            'major',
            'classes',
            'journals',
            'pendingCount'
        ));
    }

    /**
     * Monitoring rekap kunjungan supervisi guru pembimbing ke DU/DI.
     */
    public function supervisions(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $major = $this->getHeadedMajor();

        $classIds = $major->classes()->pluck('id');
        $studentIds = Student::whereIn('class_id', $classIds)->pluck('id');
        $teacherIds = StudentPkl::where('school_id', $schoolId)
            ->whereIn('student_id', $studentIds)
            ->whereNotNull('teacher_id')
            ->distinct()
            ->pluck('teacher_id');

        $supervisions = SupervisionVisit::where('school_id', $schoolId)
            ->whereIn('teacher_id', $teacherIds)
            ->with(['teacher.user', 'company'])
            ->latest('visit_date')
            ->paginate(15);

        return view('kajur.supervisions', compact('major', 'supervisions'));
    }

    /**
     * Rekapitulasi nilai akhir PKL jurusan & ekspor.
     */
    public function grades(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $major = $this->getHeadedMajor();

        $classes = $major->classes()->orderBy('name')->get();
        $classIds = $classes->pluck('id');

        $query = StudentPkl::where('school_id', $schoolId)
            ->whereHas('student', fn($q) => $q->whereIn('class_id', $classIds))
            ->with(['student.schoolClass', 'company', 'teacher.user', 'assessment']);

        if ($request->filled('class_id')) {
            $query->whereHas('student', fn($q) => $q->where('class_id', $request->class_id));
        }

        if ($request->get('export') === 'csv') {
            $records = (clone $query)->get();
            $filename = 'rekap-nilai-pkl-' . strtolower($major->code) . '-' . date('Ymd-His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($records) {
                $file = fopen('php://output', 'w');
                // UTF-8 BOM
                fputs($file, "\xEF\xBB\xBF");
                fputcsv($file, ['No', 'NISN', 'Nama Siswa', 'Kelas', 'Mitra DU/DI', 'Guru Pembimbing', 'Nilai DU/DI', 'Nilai Guru', 'Nilai Akhir', 'Status']);

                foreach ($records as $idx => $r) {
                    $ass = $r->assessment;
                    $scoreDudi = $ass ? round(($ass->score_dudi_discipline + $ass->score_dudi_technical + $ass->score_dudi_initiative) / 3, 1) : '-';
                    $scoreGuru = $ass ? $ass->score_teacher_report : '-';
                    $finalScore = $ass ? $ass->final_score : '-';

                    fputcsv($file, [
                        $idx + 1,
                        $r->student->nisn,
                        $r->student->name,
                        $r->student->schoolClass?->name ?? '-',
                        $r->company?->name ?? '-',
                        $r->teacher?->user->name ?? '-',
                        $scoreDudi,
                        $scoreGuru,
                        $finalScore,
                        $ass ? 'Sudah Dinilai' : 'Belum Lengkap',
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        $records = $query->paginate(20)->withQueryString();

        return view('kajur.grades', compact('major', 'classes', 'records'));
    }
}
