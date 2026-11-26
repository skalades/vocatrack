<?php

namespace App\Http\Requests\AdminSekolah;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            'major_id' => ['required', Rule::exists('majors', 'id')->where('school_id', $schoolId)],
            'homeroom_teacher_id' => [
                'nullable',
                Rule::exists('teachers', 'id')->where(fn($q) => $q->whereIn('user_id', User::where('school_id', $schoolId)->pluck('id'))),
            ],
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('classes', 'name')->where('school_id', $schoolId)->where('academic_year', $this->academic_year),
            ],
            'grade_level' => ['required', 'in:X,XI,XII,XIII'],
            'academic_year' => ['required', 'string', 'max:15'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Nama rombel kelas ini sudah ada untuk tahun ajaran yang dipilih.',
            'grade_level.in' => 'Tingkat kelas harus berupa X, XI, XII, atau XIII.',
        ];
    }
}
