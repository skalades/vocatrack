<?php

namespace App\Http\Requests\AdminSekolah;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:150'],
            'school_phone' => ['required', 'string', 'max:20'],
            'school_address' => ['required', 'string'],
            'school_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'checkin_start' => ['required'],
            'checkin_late' => ['required'],
            'checkin_alfa' => ['required'],
            'checkout_start' => ['required'],
            'journal_backdate_limit' => ['required', 'integer', 'min:0', 'max:30'],
            'weight_dudi' => ['required', 'numeric', 'min:0', 'max:100'],
            'weight_guru' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
