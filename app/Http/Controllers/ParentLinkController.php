<?php

namespace App\Http\Controllers;

use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\ParentMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentLinkController extends Controller
{
    public function __construct(
        protected ParentMonitoringService $parentService
    ) {}

    /**
     * Helper untuk mendapatkan School ID pengguna terautentikasi.
     */
    protected function getSchoolId(): int
    {
        return Auth::user()->school_id;
    }

    /**
     * -------------------------------------------------------------
     * BAGIAN ADMIN SEKOLAH
     * -------------------------------------------------------------
     */

    /**
     * Tampilkan halaman kelola link portal orang tua untuk Admin Sekolah.
     */
    public function adminIndex(Request $request)
    {
        $schoolId = $this->getSchoolId();

        $filters = [
            'major_id' => $request->get('major_id', 'all'),
            'class_id' => $request->get('class_id', 'all'),
            'token_status' => $request->get('token_status', 'all'),
            'portal_status' => $request->get('portal_status', 'all'),
            'search' => $request->get('search'),
        ];

        $filterOptions = $this->parentService->getFilterOptions($schoolId);

        $query = $this->parentService->getStudentsQuery($schoolId, $filters);

        // Ringkasan KPI Skalabel
        $baseStatQuery = $this->parentService->getStudentsQuery($schoolId, [
            'major_id' => $filters['major_id'],
            'class_id' => $filters['class_id'],
        ]);

        $stats = [
            'total' => (clone $baseStatQuery)->count(),
            'has_token' => (clone $baseStatQuery)->whereNotNull('parent_token')->where('parent_token', '!=', '')->count(),
            'missing_token' => (clone $baseStatQuery)->where(function ($q) {
                $q->whereNull('parent_token')->orWhere('parent_token', '');
            })->count(),
            'active_portal' => (clone $baseStatQuery)->where('is_parent_portal_active', true)->whereNotNull('parent_token')->where('parent_token', '!=', '')->count(),
            'inactive_portal' => (clone $baseStatQuery)->where('is_parent_portal_active', false)->count(),
        ];

        $students = $query->latest('students.id')->paginate(20)->withQueryString();

        return view('adminsekolah.parent-links', [
            'students' => $students,
            'majors' => $filterOptions['majors'],
            'classes' => $filterOptions['classes'],
            'stats' => $stats,
            'filters' => $filters,
            'roleType' => 'admin',
        ]);
    }

    /**
     * Batch generate token untuk Admin Sekolah.
     */
    public function adminBatchGenerate(Request $request)
    {
        $schoolId = $this->getSchoolId();

        $request->validate([
            'mode' => ['required', 'in:selected,filter'],
            'student_ids' => ['required_if:mode,selected', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'force_regenerate' => ['nullable', 'boolean'],
            'major_id' => ['nullable'],
            'class_id' => ['nullable'],
        ]);

        $force = (bool) $request->input('force_regenerate', false);

        if ($request->input('mode') === 'selected') {
            $count = $this->parentService->batchGenerateTokens(
                $request->input('student_ids', []),
                $schoolId,
                $force
            );
        } else {
            $filters = [
                'major_id' => $request->input('major_id', 'all'),
                'class_id' => $request->input('class_id', 'all'),
            ];
            $count = $this->parentService->batchGenerateByFilter(
                $schoolId,
                $filters,
                $force
            );
        }

        $verb = $force ? 'diperbarui / dibuat ulang' : 'dibuat';
        return back()->with('success', "Berhasil! Sebanyak {$count} tautan monitoring orang tua siswa telah {$verb}.");
    }

    /**
     * Batch toggle portal aktif/nonaktif untuk Admin Sekolah.
     */
    public function adminBatchToggle(Request $request)
    {
        $schoolId = $this->getSchoolId();

        $request->validate([
            'student_ids' => ['required', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $request->input('is_active');
        $count = $this->parentService->batchTogglePortal(
            $request->input('student_ids'),
            $schoolId,
            $isActive
        );

        $statusText = $isActive ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akses portal monitoring orang tua untuk {$count} siswa berhasil {$statusText}.");
    }

    /**
     * Ekspor CSV link orang tua untuk Admin Sekolah berdasarkan jurusan dan rombel.
     */
    public function adminExport(Request $request)
    {
        $schoolId = $this->getSchoolId();

        $filters = [
            'major_id' => $request->get('major_id', 'all'),
            'class_id' => $request->get('class_id', 'all'),
            'token_status' => $request->get('token_status', 'all'),
            'portal_status' => $request->get('portal_status', 'all'),
            'search' => $request->get('search'),
            'student_ids' => $request->get('student_ids'),
        ];

        return $this->parentService->exportStudentsCsvStream($schoolId, $filters);
    }

    /**
     * Regenerate token tunggal untuk Admin Sekolah.
     */
    public function adminRegenerateSingle(Student $student)
    {
        $schoolId = $this->getSchoolId();
        abort_if($student->user->school_id !== $schoolId, 403, 'Akses ditolak.');

        $newToken = $this->parentService->regenerateToken($student);

        return back()->with('success', "Tautan monitoring orang tua untuk {$student->user->name} berhasil diperbarui.");
    }

    /**
     * Toggle status aktif portal tunggal untuk Admin Sekolah.
     */
    public function adminToggleSingle(Request $request, Student $student)
    {
        $schoolId = $this->getSchoolId();
        abort_if($student->user->school_id !== $schoolId, 403, 'Akses ditolak.');

        $newState = !$student->is_parent_portal_active;
        $this->parentService->togglePortal($student, $newState);

        $msg = $newState ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akses portal orang tua untuk {$student->user->name} berhasil {$msg}.");
    }


    /**
     * -------------------------------------------------------------
     * BAGIAN GURU PEMBIMBING
     * -------------------------------------------------------------
     */

    /**
     * Tampilkan halaman kelola link portal orang tua untuk Guru Pembimbing.
     */
    public function guruIndex(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $teacher = Auth::user()->teacher;
        abort_if(!$teacher, 403, 'Profil guru tidak ditemukan.');

        $filters = [
            'scope' => $request->get('scope', 'bimbingan'), // bimbingan | all
            'major_id' => $request->get('major_id', 'all'),
            'class_id' => $request->get('class_id', 'all'),
            'token_status' => $request->get('token_status', 'all'),
            'portal_status' => $request->get('portal_status', 'all'),
            'search' => $request->get('search'),
        ];

        $filterOptions = $this->parentService->getFilterOptions($schoolId, $teacher->id);

        $teacherId = $filters['scope'] === 'bimbingan' ? $teacher->id : null;
        $query = $this->parentService->getStudentsQuery($schoolId, $filters, $teacherId);

        // Ringkasan KPI Skalabel
        $baseStatQuery = $this->parentService->getStudentsQuery($schoolId, [
            'scope' => $filters['scope'],
            'major_id' => $filters['major_id'],
            'class_id' => $filters['class_id'],
        ], $teacherId);

        $stats = [
            'total' => (clone $baseStatQuery)->count(),
            'has_token' => (clone $baseStatQuery)->whereNotNull('parent_token')->where('parent_token', '!=', '')->count(),
            'missing_token' => (clone $baseStatQuery)->where(function ($q) {
                $q->whereNull('parent_token')->orWhere('parent_token', '');
            })->count(),
            'active_portal' => (clone $baseStatQuery)->where('is_parent_portal_active', true)->whereNotNull('parent_token')->where('parent_token', '!=', '')->count(),
            'inactive_portal' => (clone $baseStatQuery)->where('is_parent_portal_active', false)->count(),
        ];

        $students = $query->latest('students.id')->paginate(20)->withQueryString();

        return view('teacher.parent-links', [
            'students' => $students,
            'majors' => $filterOptions['majors'],
            'classes' => $filterOptions['classes'],
            'stats' => $stats,
            'filters' => $filters,
            'roleType' => 'guru',
            'teacher' => $teacher,
        ]);
    }

    /**
     * Batch generate token untuk Guru Pembimbing.
     */
    public function guruBatchGenerate(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $teacher = Auth::user()->teacher;
        abort_if(!$teacher, 403, 'Profil guru tidak ditemukan.');

        $request->validate([
            'mode' => ['required', 'in:selected,filter'],
            'student_ids' => ['required_if:mode,selected', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'force_regenerate' => ['nullable', 'boolean'],
            'scope' => ['nullable', 'in:bimbingan,all'],
            'major_id' => ['nullable'],
            'class_id' => ['nullable'],
        ]);

        $force = (bool) $request->input('force_regenerate', false);
        $teacherId = $request->input('scope', 'bimbingan') === 'bimbingan' ? $teacher->id : null;

        if ($request->input('mode') === 'selected') {
            $count = $this->parentService->batchGenerateTokens(
                $request->input('student_ids', []),
                $schoolId,
                $force,
                $teacherId
            );
        } else {
            $filters = [
                'scope' => $request->input('scope', 'bimbingan'),
                'major_id' => $request->input('major_id', 'all'),
                'class_id' => $request->input('class_id', 'all'),
            ];
            $count = $this->parentService->batchGenerateByFilter(
                $schoolId,
                $filters,
                $force,
                $teacherId
            );
        }

        $verb = $force ? 'diperbarui / dibuat ulang' : 'dibuat';
        return back()->with('success', "Berhasil! Sebanyak {$count} tautan monitoring orang tua siswa telah {$verb}.");
    }

    /**
     * Batch toggle portal aktif/nonaktif untuk Guru Pembimbing.
     */
    public function guruBatchToggle(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $teacher = Auth::user()->teacher;
        abort_if(!$teacher, 403, 'Profil guru tidak ditemukan.');

        $request->validate([
            'student_ids' => ['required', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'is_active' => ['required', 'boolean'],
            'scope' => ['nullable', 'in:bimbingan,all'],
        ]);

        $isActive = (bool) $request->input('is_active');
        $teacherId = $request->input('scope', 'bimbingan') === 'bimbingan' ? $teacher->id : null;

        $count = $this->parentService->batchTogglePortal(
            $request->input('student_ids'),
            $schoolId,
            $isActive,
            $teacherId
        );

        $statusText = $isActive ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akses portal monitoring orang tua untuk {$count} siswa berhasil {$statusText}.");
    }

    /**
     * Ekspor CSV link orang tua untuk Guru Pembimbing berdasarkan jurusan dan rombel.
     */
    public function guruExport(Request $request)
    {
        $schoolId = $this->getSchoolId();
        $teacher = Auth::user()->teacher;
        abort_if(!$teacher, 403, 'Profil guru tidak ditemukan.');

        $filters = [
            'scope' => $request->get('scope', 'bimbingan'),
            'major_id' => $request->get('major_id', 'all'),
            'class_id' => $request->get('class_id', 'all'),
            'token_status' => $request->get('token_status', 'all'),
            'portal_status' => $request->get('portal_status', 'all'),
            'search' => $request->get('search'),
            'student_ids' => $request->get('student_ids'),
        ];

        $teacherId = $filters['scope'] === 'bimbingan' ? $teacher->id : null;

        return $this->parentService->exportStudentsCsvStream($schoolId, $filters, $teacherId);
    }

    /**
     * Regenerate token tunggal untuk Guru Pembimbing.
     */
    public function guruRegenerateSingle(Student $student)
    {
        $schoolId = $this->getSchoolId();
        $teacher = Auth::user()->teacher;
        abort_if(!$teacher, 403, 'Profil guru tidak ditemukan.');
        abort_if($student->user->school_id !== $schoolId, 403, 'Akses ditolak.');

        $newToken = $this->parentService->regenerateToken($student);

        return back()->with('success', "Tautan monitoring orang tua untuk {$student->user->name} berhasil diperbarui.");
    }

    /**
     * Toggle status aktif portal tunggal untuk Guru Pembimbing.
     */
    public function guruToggleSingle(Request $request, Student $student)
    {
        $schoolId = $this->getSchoolId();
        $teacher = Auth::user()->teacher;
        abort_if(!$teacher, 403, 'Profil guru tidak ditemukan.');
        abort_if($student->user->school_id !== $schoolId, 403, 'Akses ditolak.');

        $newState = !$student->is_parent_portal_active;
        $this->parentService->togglePortal($student, $newState);

        $msg = $newState ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akses portal orang tua untuk {$student->user->name} berhasil {$msg}.");
    }
}
