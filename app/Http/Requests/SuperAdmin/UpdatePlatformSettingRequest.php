<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'platform_name' => ['required', 'string', 'max:60'],
            'platform_tagline' => ['nullable', 'string', 'max:100'],
            'app_description' => ['nullable', 'string', 'max:255'],
            'system_logo' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'reset_logo' => ['nullable', 'boolean'],
            'maintenance_active' => ['nullable', 'boolean'],
            'maintenance_notice' => ['nullable', 'string', 'max:500'],
            // SMTP Integrations
            'mail_host' => ['nullable', 'string', 'max:150'],
            'mail_port' => ['nullable', 'integer'],
            'mail_username' => ['nullable', 'string', 'max:100'],
            'mail_password' => ['nullable', 'string', 'max:100'],
            'mail_encryption' => ['nullable', 'string', 'in:tls,ssl,none'],
            'mail_from_address' => ['nullable', 'email', 'max:100'],
            'mail_from_name' => ['nullable', 'string', 'max:100'],
            // WhatsApp Gateway Integrations
            'wa_provider' => ['nullable', 'string', 'max:50'],
            'wa_api_endpoint' => ['nullable', 'string', 'max:200'],
            'wa_api_token' => ['nullable', 'string', 'max:255'],
            'wa_sender_phone' => ['nullable', 'string', 'max:25'],
            // Cloud Storage Settings
            'storage_driver' => ['nullable', 'string', 'in:public,s3,r2'],
            's3_bucket' => ['nullable', 'string', 'max:100'],
            's3_region' => ['nullable', 'string', 'max:50'],
        ];
    }
}
