<?php

namespace App\Http\Requests\AdminSekolah;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;
        $student = $this->route('student');

        return [
            'class_id' => ['required', Rule::exists('classes', 'id')->where('school_id', $schoolId)],
            'nis' => ['required', 'string', 'max:30'],
            'nisn' => ['required', 'string', 'max:30', Rule::unique('students', 'nisn')->ignore($student?->id ?? $student)],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($student?->user_id ?? ($student?->user?->id ?? null))],
            'gender' => ['required', 'in:L,P'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
