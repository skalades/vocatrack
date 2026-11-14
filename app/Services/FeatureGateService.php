<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class FeatureGateService
{
    /**
     * Check if a given school is entitled to access a specific SaaS feature.
     */
    public function isFeatureEnabled(School $school, string $feature): bool
    {
        return $school->hasFeature($feature);
    }

    /**
     * Enforce that a school must have the feature, throwing ValidationException if denied.
     */
    public function authorizeFeature(School $school, string $feature, ?string $customMessage = null): void
    {
        if (!$this->isFeatureEnabled($school, $feature)) {
            $featureLabels = [
                'certificate' => 'Penerbitan E-Sertifikat & Transkrip PKL',
                'whatsapp_gateway' => 'Notifikasi WhatsApp Otomatis Gateway',
                'custom_domain' => 'Penggunaan Domain Kustom Sekolah',
                'assessment' => 'Penilaian Akademik & Praktik Terpadu',
                'journal' => 'Review Jurnal PKL Harian',
                'attendance' => 'Presensi Selfie & Geofencing GPS',
            ];

            $label = $featureLabels[$feature] ?? ucfirst(str_replace('_', ' ', $feature));
            $message = $customMessage ?? "Fitur '{$label}' tidak aktif pada paket langganan sekolah Anda. Silakan tingkatkan paket langganan ke paket Pro/Enterprise untuk membuka fitur ini.";

            throw ValidationException::withMessages([
                'feature' => $message,
            ]);
        }
    }
}
