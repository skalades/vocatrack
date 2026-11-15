<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'custom_quota' => ['nullable', 'integer', 'min:10'],
            'payment_status' => ['nullable', 'in:paid,unpaid'],
            'payment_method' => ['nullable', 'string', 'max:100'],
        ];
    }
}
