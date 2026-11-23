<?php

namespace App\Http\Requests\AdminSekolah;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string'],
            'contact_person' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'work_days' => ['required', 'in:5_days,6_days,7_days'],
            'default_start_date' => ['nullable', 'date'],
            'default_end_date' => ['nullable', 'date', 'after_or_equal:default_start_date'],
            'requires_industry_approval' => ['nullable', 'boolean'],
            'is_pkl_started' => ['nullable', 'boolean'],
            'gps_enabled' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'radius_meters' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'major_ids' => ['nullable', 'array'],
            'major_ids.*' => [Rule::exists('majors', 'id')->where('school_id', $schoolId)],
            'supervisor_name' => ['nullable', 'string', 'max:100'],
            'supervisor_email' => ['nullable', 'email', 'unique:users,email'],
            'supervisor_position' => ['nullable', 'string', 'max:100'],
        ];
    }
}
