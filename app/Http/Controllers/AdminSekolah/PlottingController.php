<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Http\Requests\AdminSekolah\StorePlottingRequest;
use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use App\Services\WhatsAppGatewayService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlottingController extends BaseAdminSekolahController
{
    public function plotting(Request $request)
    {
        $schoolId = $this->getSchoolId();

        $periods = PklPeriod::where('school_id', $schoolId)->withCount('studentPkls')->latest()->get();
        $activePeriod = $periods->firstWhere('is_active', true) ?? $periods->first();
        $selectedPeriodId = $request->query('period_id', $activePeriod?->id);

        $query = StudentPkl::where('school_id', $schoolId)
            ->with(['student.user.profile', 'student.class.major', 'company.majors', 'company.supervisors.user', 'teacher.user', 'supervisor.user', 'pklPeriod']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('student.user', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('student', fn($sq) => $sq->where('nisn', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%"))
                  ->orWhereHas('company', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('teacher.user', fn($tq) => $tq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('teacher', fn($tq) => $tq->where('nip', 'like', "%{$search}%"));
            });
        }

        if ($majorId = $request->query('major_id')) {
            if ($majorId !== 'all') {
                $query->whereHas('student.class', fn($cq) => $cq->where('major_id', $majorId));
            }
        }

        if ($companyId = $request->query('company_id')) {
            if ($companyId !== 'all') {
                $query->where('company_id', $companyId);
            }
        }

        if ($statusFilter = $request->query('status')) {
            if ($statusFilter === 'active') {
                $query->where('status', 'active')
                      ->whereHas('company', fn($cq) => $cq->where('is_pkl_started', true));
            } elseif ($statusFilter === 'pending_start') {
                $query->where('status', 'active')
                      ->whereHas('company', fn($cq) => $cq->where('is_pkl_started', false));
            } elseif ($statusFilter === 'completed') {
                $query->where('status', 'completed');
            } elseif ($statusFilter === 'withdrawn') {
                $query->where('status', 'withdrawn');
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

        $studentPkls = $query->latest()->paginate(20)->withQueryString();

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

        $companies = Company::where('school_id', $schoolId)->with(['supervisors.user', 'majors'])->get();
        $teachers = Teacher::whereHas('user', fn($q) => $q->where('school_id', $schoolId))
            ->with('user')
            ->get()
            ->sortBy(fn($t) => mb_strtolower($t->user->name ?? ''))
            ->values();
        $majors = Major::where('school_id', $schoolId)->with('classes')->orderBy('name')->get();
        $classes = SchoolClass::where('school_id', $schoolId)->with('major')->orderBy('name')->get();

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

    public function storePlotting(StorePlottingRequest $request)
    {
        $schoolId = $this->getSchoolId();

        $studentIds = $request->has('student_ids') && is_array($request->student_ids) && count($request->student_ids) > 0
            ? array_unique(array_filter($request->student_ids))
            : ($request->filled('student_id') ? [$request->student_id] : []);

        if (empty($studentIds)) {
            return back()->withErrors(['student_id' => 'Pilih setidaknya satu siswa untuk diplot.']);
        }

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

            $company = Company::where('school_id', $schoolId)->find($request->company_id);
            $period = PklPeriod::where('school_id', $schoolId)->find($request->pkl_period_id);

            try {
                foreach ($studentIds as $sId) {
                    $student = Student::with('class.major')->find($sId);
                    $major = $student?->class?->major;

                    $resolvedStartDate = $request->filled('start_date')
                        ? $request->start_date
                        : ($company?->default_start_date?->toDateString() ?? $period?->start_date?->toDateString());

                    $resolvedEndDate = $request->filled('end_date')
                        ? $request->end_date
                        : ($company?->default_end_date?->toDateString() ?? null);

                    if (!$resolvedEndDate && $resolvedStartDate && $major?->default_pkl_duration_months) {
                        $resolvedEndDate = Carbon::parse($resolvedStartDate)
                            ->addMonths($major->default_pkl_duration_months)
                            ->toDateString();
                    }

                    $resolvedEndDate = $resolvedEndDate ?? $period?->end_date?->toDateString();

                    StudentPkl::create([
                        'school_id' => $schoolId,
                        'student_id' => $sId,
                        'company_id' => $request->company_id,
                        'pkl_period_id' => $request->pkl_period_id,
                        'teacher_id' => $request->teacher_id,
                        'supervisor_id' => $request->supervisor_id,
                        'start_date' => $resolvedStartDate,
                        'end_date' => $resolvedEndDate,
                        'status' => 'active',
                    ]);
                }
            } catch (QueryException $e) {
                if (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Integrity constraint violation')) {
                    return back()->withErrors(['student_id' => 'Siswa sudah diplot ke DU/DI pada periode PKL yang sama.']);
                }
                throw $e;
            }

            $count = count($studentIds);
            return back()->with('success', "Penugasan PKL untuk {$count} siswa berhasil disimpan.");
        });
    }

    public function reassignPlotting(Request $request, StudentPkl $studentPkl)
    {
        $schoolId = $this->getSchoolId();
        abort_if($studentPkl->school_id !== $schoolId, 403);

        $request->validate([
            'teacher_id' => [
                'required',
                Rule::exists('teachers', 'id')->where(function ($query) use ($schoolId) {
                    $query->whereIn('user_id', User::where('school_id', $schoolId)->select('id'));
                }),
            ],
            'supervisor_id' => [
                'nullable',
                Rule::exists('supervisors', 'id')->where('company_id', $studentPkl->company_id),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $updateData = [
            'teacher_id' => $request->teacher_id,
            'supervisor_id' => $request->filled('supervisor_id') ? $request->supervisor_id : null,
        ];

        if ($request->has('start_date')) {
            $updateData['start_date'] = $request->start_date ?: null;
        }
        if ($request->has('end_date')) {
            $updateData['end_date'] = $request->end_date ?: null;
        }

        $studentPkl->update($updateData);

        $studentName = $studentPkl->student->user->name ?? 'Siswa';
        return back()->with('success', "Data penugasan dan rentang tanggal PKL untuk {$studentName} berhasil diperbarui.");
    }

    public function destroyPlotting(StudentPkl $studentPkl)
    {
        $schoolId = $this->getSchoolId();
        abort_if($studentPkl->school_id !== $schoolId, 403);

        $hasAttendances = $studentPkl->attendances()->exists();
        $hasJournals = $studentPkl->journals()->exists();
        $hasAssessment = $studentPkl->assessment()->exists();

        if ($hasAttendances || $hasJournals || $hasAssessment) {
            return back()->withErrors([
                'student_pkl' => "Tidak dapat membatalkan plotting untuk {$studentPkl->student->user->name} karena sudah ada data riwayat presensi, jurnal, atau penilaian."
            ]);
        }

        $studentName = $studentPkl->student->user->name;
        $studentPkl->delete();

        return back()->with('success', "Penugasan PKL untuk siswa {$studentName} berhasil dibatalkan (unplot).");
    }

    public function sendParentLink(Request $request, StudentPkl $studentPkl, WhatsAppGatewayService $waGateway)
    {
        $schoolId = $this->getSchoolId();
        abort_if($studentPkl->school_id !== $schoolId, 403);

        $student = $studentPkl->student;
        if (!$student) {
            return back()->with('error', 'Data siswa tidak ditemukan.');
        }

        if (empty($student->parent_token)) {
            $student->regenerateParentToken();
            $student->refresh();
        }

        if (!$student->is_parent_portal_active) {
            $student->update(['is_parent_portal_active' => true]);
        }

        $user = $student->user;
        $rawPhone = $user?->wa_number ?: ($user?->profile?->phone ?? null);
        $normalizedPhone = WhatsAppGatewayService::normalizePhoneNumber($rawPhone);

        if (empty($normalizedPhone) || !WhatsAppGatewayService::isValidIndonesianNumber($normalizedPhone)) {
            $displayName = $user?->name ?? 'Siswa';
            $phoneNotice = $rawPhone ? "({$rawPhone} - format nomor tidak valid)" : '(nomor HP belum terdaftar)';
            $errorMsg = "Gagal mengirim link: Nomor WhatsApp/telepon siswa {$displayName} {$phoneNotice}. Silakan lengkapi kontak nomor telepon siswa pada menu Data Siswa terlebih dahulu.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }

            return back()->with('error', $errorMsg);
        }

        $parentUrl = route('parent.monitor', ['token' => $student->parent_token]);
        $companyName = $studentPkl->company?->name ?? 'DU/DI Mitra';
        $teacherName = $studentPkl->teacher?->user?->name ?? 'Guru Pembimbing';
        $schoolName = $user?->school?->name ?? 'Sekolah';
        $className = $student->class?->name ?? '-';
        $majorName = $student->class?->major?->name ?? '-';

        $message = "Yth. Orang Tua / Wali dari {$user?->name} ({$className} - {$majorName}),\n\n"
                 . "Berikut adalah tautan resmi Portal Pemantauan Aktivitas PKL (Praktik Kerja Lapangan) putra/putri Anda:\n"
                 . "👉 {$parentUrl}\n\n"
                 . "🏢 Mitra DU/DI: {$companyName}\n"
                 . "👨‍🏫 Guru Pembimbing: {$teacherName}\n\n"
                 . "Melalui tautan di atas, Bapak/Ibu dapat memantau presensi harian, jurnal kegiatan industri, serta rekap kehadiran secara langsung tanpa perlu login atau memasukkan kata sandi.\n\n"
                 . "Terima kasih atas bimbingan dan kerja samanya.\n"
                 . "— Tim PKL {$schoolName}";

        $sent = $waGateway->sendMessage($normalizedPhone, $message);

        if ($sent) {
            $successMsg = "Link portal pemantauan orang tua untuk {$user?->name} berhasil dikirimkan otomatis oleh sistem ke nomor siswa ({$normalizedPhone}).";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'phone' => $normalizedPhone,
                    'url' => $parentUrl,
                ]);
            }

            return back()->with('success', $successMsg);
        }

        $failMsg = "Gagal mengirim pesan otomatis ke nomor {$normalizedPhone}. Pastikan koneksi gateway WhatsApp server aktif.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $failMsg], 500);
        }

        return back()->with('error', $failMsg);
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
}
