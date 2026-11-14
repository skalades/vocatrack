<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class ExtendSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'months' => ['required', 'integer', 'in:1,3,6,12,24'],
            'price' => ['required', 'numeric', 'min:0'],
            'new_quota' => ['nullable', 'integer', 'min:10'],
            'custom_storage_mb' => ['nullable', 'integer', 'min:100'],
            'notes' => ['nullable', 'string', 'max:255'],
            'plan_id' => ['nullable', 'exists:subscription_plans,id'],
        ];
    }
}
