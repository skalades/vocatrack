<?php

namespace App\Http\Controllers\AdminSekolah;

use App\Http\Requests\AdminSekolah\StoreClassRequest;
use App\Http\Requests\AdminSekolah\StoreMajorRequest;
use App\Models\LearningObjective;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ClassPromotionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicController extends BaseAdminSekolahController
{
    public function majors()
    {
        $schoolId = $this->getSchoolId();
        $majors = Major::where('school_id', $schoolId)
            ->with(['headTeacher.user', 'learningObjectives'])
            ->withCount(['classes', 'learningObjectives'])
            ->get();
        $classes = SchoolClass::where('school_id', $schoolId)->with('major')->get();
        $teachers = Teacher::whereHas('user', fn($q) => $q->where('school_id', $schoolId)->where('is_active', true))
            ->with('user')
            ->get();

        return view('adminsekolah.majors', compact('majors', 'classes', 'teachers'));
    }

    public function storeMajor(StoreMajorRequest $request)
    {
        $schoolId = $this->getSchoolId();

        Major::create([
            'school_id' => $schoolId,
            'head_teacher_id' => $request->head_teacher_id,
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'program_duration_years' => (int) $request->input('program_duration_years', 3),
            'default_pkl_duration_months' => (int) $request->input('default_pkl_duration_months', 6),
        ]);

        return back()->with('success', 'Jurusan keahlian berhasil ditambahkan.');
    }

    public function updateMajor(Request $request, Major $major)
    {
        $schoolId = $this->getSchoolId();
        abort_if($major->school_id !== $schoolId, 403);

        $request->validate([
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('majors', 'code')->where('school_id', $schoolId)->ignore($major->id),
            ],
            'name' => ['required', 'string', 'max:100'],
            'program_duration_years' => ['nullable', 'integer', 'in:3,4'],
            'default_pkl_duration_months' => ['nullable', 'integer', 'min:1', 'max:24'],
            'head_teacher_id' => [
                'nullable',
                Rule::exists('teachers', 'id')->where(fn($q) => $q->whereIn('user_id', User::where('school_id', $schoolId)->pluck('id'))),
            ],
        ], [
            'code.unique' => 'Kode jurusan ini sudah digunakan.',
        ]);

        $major->update([
            'head_teacher_id' => $request->head_teacher_id,
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'program_duration_years' => (int) $request->input('program_duration_years', $major->program_duration_years ?? 3),
            'default_pkl_duration_months' => (int) $request->input('default_pkl_duration_months', $major->default_pkl_duration_months ?? 6),
        ]);

        return back()->with('success', 'Data jurusan berhasil diperbarui.');
    }

    public function destroyMajor(Major $major)
    {
        $schoolId = $this->getSchoolId();
        abort_if($major->school_id !== $schoolId, 403);

        if ($major->classes()->exists()) {
            return back()->withErrors(['major' => 'Jurusan tidak dapat dihapus karena masih memiliki rombel kelas terdaftar.']);
        }

        $major->delete();
        return back()->with('success', 'Jurusan berhasil dihapus.');
    }

    public function storeMajorCompetency(Request $request, Major $major)
    {
        $schoolId = $this->getSchoolId();
        abort_if($major->school_id !== $schoolId, 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'title' => ['required', 'string', 'max:255'],
            'element_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'target_hours' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        LearningObjective::create([
            'school_id' => $schoolId,
            'major_id' => $major->id,
            'code' => strtoupper($validated['code']),
            'title' => $validated['title'],
            'element_name' => $validated['element_name'] ?? 'Keterampilan Teknis Kejuruan',
            'description' => $validated['description'] ?? null,
            'target_hours' => $validated['target_hours'] ?? 0,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Capaian Pembelajaran (CP) berhasil ditambahkan ke jurusan ' . $major->code . '.');
    }

    public function updateMajorCompetency(Request $request, LearningObjective $learningObjective)
    {
        $schoolId = $this->getSchoolId();
        abort_if($learningObjective->school_id !== $schoolId, 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'title' => ['required', 'string', 'max:255'],
            'element_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'target_hours' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $learningObjective->update([
            'code' => strtoupper($validated['code']),
            'title' => $validated['title'],
            'element_name' => $validated['element_name'] ?? 'Keterampilan Teknis Kejuruan',
            'description' => $validated['description'] ?? null,
            'target_hours' => $validated['target_hours'] ?? 0,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $learningObjective->is_active,
        ]);

        return back()->with('success', 'Data Capaian Pembelajaran berhasil diperbarui.');
    }

    public function destroyMajorCompetency(LearningObjective $learningObjective)
    {
        $schoolId = $this->getSchoolId();
        abort_if($learningObjective->school_id !== $schoolId, 403);

        $learningObjective->delete();

        return back()->with('success', 'Capaian Pembelajaran berhasil dihapus.');
    }

    public function storeClass(StoreClassRequest $request)
    {
        $schoolId = $this->getSchoolId();

        $major = Major::where('school_id', $schoolId)->findOrFail($request->major_id);
        if ($request->grade_level === 'XIII' && !$major->isFourYearProgram()) {
            return back()->withErrors(['grade_level' => "Tingkat Kelas XIII hanya diperuntukkan bagi jurusan program 4 tahun ({$major->name} saat ini disetel program 3 tahun)."]);
        }

        SchoolClass::create([
            'school_id' => $schoolId,
            'major_id' => $request->major_id,
            'name' => $request->name,
            'grade_level' => $request->grade_level,
            'academic_year' => $request->academic_year,
        ]);

        return back()->with('success', 'Kelas berhasil didaftarkan.');
    }

    public function updateClass(Request $request, SchoolClass $schoolClass)
    {
        $schoolId = $this->getSchoolId();
        abort_if($schoolClass->school_id !== $schoolId, 403);

        $request->validate([
            'major_id' => ['required', Rule::exists('majors', 'id')->where('school_id', $schoolId)],
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('classes', 'name')->where('school_id', $schoolId)->where('academic_year', $request->academic_year)->ignore($schoolClass->id),
            ],
            'grade_level' => ['required', 'in:X,XI,XII,XIII'],
            'academic_year' => ['required', 'string', 'max:15'],
        ], [
            'name.unique' => 'Nama rombel kelas ini sudah ada untuk tahun ajaran yang dipilih.',
            'grade_level.in' => 'Tingkat kelas harus berupa X, XI, XII, atau XIII.',
        ]);

        $major = Major::where('school_id', $schoolId)->findOrFail($request->major_id);
        if ($request->grade_level === 'XIII' && !$major->isFourYearProgram()) {
            return back()->withErrors(['grade_level' => "Tingkat Kelas XIII hanya diperuntukkan bagi jurusan program 4 tahun ({$major->name} saat ini disetel program 3 tahun)."]);
        }

        $schoolClass->update([
            'major_id' => $request->major_id,
            'name' => $request->name,
            'grade_level' => $request->grade_level,
            'academic_year' => $request->academic_year,
        ]);

        return back()->with('success', 'Data rombel kelas berhasil diperbarui.');
    }

    public function destroyClass(SchoolClass $schoolClass)
    {
        $schoolId = $this->getSchoolId();
        abort_if($schoolClass->school_id !== $schoolId, 403);

        if ($schoolClass->students()->exists()) {
            return back()->withErrors(['class' => 'Kelas tidak dapat dihapus karena masih memiliki siswa terdaftar di dalamnya.']);
        }

        $schoolClass->delete();
        return back()->with('success', 'Rombel kelas berhasil dihapus.');
    }

    public function previewPromotion(Request $request, ClassPromotionService $promotionService)
    {
        $schoolId = $this->getSchoolId();
        $request->validate([
            'current_academic_year' => ['required', 'string'],
            'target_academic_year' => ['required', 'string'],
        ]);

        $preview = $promotionService->previewPromotion(
            $schoolId,
            $request->current_academic_year,
            $request->target_academic_year
        );

        return response()->json([
            'success' => true,
            'preview' => $preview,
        ]);
    }

    public function promoteClasses(Request $request, ClassPromotionService $promotionService)
    {
        $schoolId = $this->getSchoolId();
        $request->validate([
            'current_academic_year' => ['required', 'string'],
            'target_academic_year' => ['required', 'string'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => [Rule::exists('classes', 'id')->where('school_id', $schoolId)],
        ]);

        $stats = $promotionService->executePromotion(
            $schoolId,
            $request->current_academic_year,
            $request->target_academic_year,
            $request->input('class_ids', [])
        );

        $msg = "Proses kenaikan kelas berhasil: {$stats['promoted_students_count']} siswa naik tingkat, {$stats['graduated_students_count']} siswa dinyatakan lulus (alumni).";
        return back()->with('success', $msg);
    }
}
