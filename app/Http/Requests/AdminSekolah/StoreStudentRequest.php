<?php

namespace App\Http\Requests\AdminSekolah;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            'class_id' => ['required', Rule::exists('classes', 'id')->where('school_id', $schoolId)],
            'nis' => ['required', 'string', 'max:30'],
            'nisn' => ['required', 'string', 'max:30', 'unique:students,nisn'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'gender' => ['required', 'in:L,P'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
