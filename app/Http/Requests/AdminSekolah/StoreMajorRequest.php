<?php

namespace App\Http\Requests\AdminSekolah;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMajorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('majors', 'code')->where('school_id', $schoolId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'program_duration_years' => ['nullable', 'integer', 'in:3,4'],
            'default_pkl_duration_months' => ['nullable', 'integer', 'min:1', 'max:24'],
            'head_teacher_id' => [
                'nullable',
                Rule::exists('teachers', 'id')->where(fn($q) => $q->whereIn('user_id', User::where('school_id', $schoolId)->pluck('id'))),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Kode jurusan ini sudah digunakan.',
        ];
    }
}
