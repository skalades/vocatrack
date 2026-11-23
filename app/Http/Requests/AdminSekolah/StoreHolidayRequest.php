<?php

namespace App\Http\Requests\AdminSekolah;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            'title' => ['required', 'string', 'max:150'],
            'holiday_date' => [
                'required',
                'date',
                Rule::unique('school_holidays')->where(fn($q) => $q->where('school_id', $schoolId)),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_national' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'holiday_date.unique' => 'Tanggal ini sudah terdaftar sebagai hari libur.',
        ];
    }
}
