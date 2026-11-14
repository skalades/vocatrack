<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'npsn' => ['required', 'string', 'size:8', 'unique:schools,npsn'],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:100'],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'unique:users,email'],
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'custom_quota' => ['nullable', 'integer', 'min:10'],
            'custom_storage_mb' => ['nullable', 'integer', 'min:100'],
        ];
    }
}
