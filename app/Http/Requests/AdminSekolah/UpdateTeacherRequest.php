<?php

namespace App\Http\Requests\AdminSekolah;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;
        $teacher = $this->route('teacher');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($teacher?->user_id ?? ($teacher?->user?->id ?? null))],
            'nip' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:20'],
            'major_ids' => ['nullable', 'array'],
            'major_ids.*' => [Rule::exists('majors', 'id')->where('school_id', $schoolId)],
        ];
    }
}
