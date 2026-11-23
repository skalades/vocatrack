<?php

namespace App\Http\Requests\AdminSekolah;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlottingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
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
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
