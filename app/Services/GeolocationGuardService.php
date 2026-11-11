<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Validation\ValidationException;

class GeolocationGuardService
{
    /**
     * Threshold in meters beyond which GPS signal is deemed too weak/unreliable for valid checkin.
     */
    public const MAX_ACCURACY_THRESHOLD_METERS = 80;

    /**
     * Calculate Haversine distance in meters between two lat/lng coordinates.
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * (sin($dLon / 2) ** 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Validate client GPS accuracy readings.
     */
    public function validateAccuracy(?int $accuracyMeters): array
    {
        if ($accuracyMeters === null) {
            return [
                'level' => 'unknown',
                'is_blocked' => false,
                'message' => 'Tingkat akurasi GPS tidak dilaporkan oleh peramban.',
            ];
        }

        if ($accuracyMeters > self::MAX_ACCURACY_THRESHOLD_METERS) {
            return [
                'level' => 'weak',
                'is_blocked' => true,
                'message' => "Sinyal GPS tidak akurat (±{$accuracyMeters} meter, batas toleransi maksimal: " . self::MAX_ACCURACY_THRESHOLD_METERS . "m). Silakan melangkah ke area terbuka untuk kalibrasi sinyal satelit.",
            ];
        }

        if ($accuracyMeters > 30) {
            return [
                'level' => 'moderate',
                'is_blocked' => false,
                'message' => "Sinyal GPS sedang (±{$accuracyMeters} meter).",
            ];
        }

        return [
            'level' => 'accurate',
            'is_blocked' => false,
            'message' => "Sinyal GPS sangat akurat (±{$accuracyMeters} meter).",
        ];
    }

    /**
     * Detect mock location / GPS spoofing indicators from client payload and headers.
     */
    public function detectMockLocation(array $data, ?string $userAgent = null): bool
    {
        // 1. Explicit client flag (supported by modern mobile web / Android WebView wrappers)
        if (!empty($data['is_mocked']) || !empty($data['is_mock_detected'])) {
            return true;
        }

        // 2. Suspicious 0-meter accuracy with non-zero lat/lng (typical fake GPS app artifact)
        if (isset($data['accuracy']) && (float) $data['accuracy'] === 0.0 && !empty($data['latitude'])) {
            return true;
        }

        // 3. Obvious emulator / developer instrumentation artifacts in User Agent if present
        if ($userAgent) {
            $uaLower = strtolower($userAgent);
            if (str_contains($uaLower, 'fakegps') || str_contains($uaLower, 'mocklocation')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Comprehensive evaluation of check-in geolocation coordinates, radius, accuracy, and spoof detection.
     */
    public function evaluateCheckinLocation(Company $company, array $data, ?string $userAgent = null): array
    {
        $accuracy = isset($data['accuracy']) ? (int) round((float) $data['accuracy']) : null;
        $isMock = $this->detectMockLocation($data, $userAgent);

        // Guard against weak GPS signal if GPS enforcement is enabled
        if ($company->gps_enabled) {
            if (!isset($data['latitude']) || !isset($data['longitude'])) {
                throw ValidationException::withMessages([
                    'location' => 'Data koordinat GPS perangkat diperlukan untuk presensi di DU/DI ini.',
                ]);
            }

            if ($accuracy !== null) {
                $accuracyCheck = $this->validateAccuracy($accuracy);
                if ($accuracyCheck['is_blocked']) {
                    throw ValidationException::withMessages([
                        'accuracy' => $accuracyCheck['message'],
                    ]);
                }
            }

            $distance = $this->calculateDistance(
                (float) $data['latitude'],
                (float) $data['longitude'],
                (float) $company->latitude,
                (float) $company->longitude
            );

            $isWithinRadius = ($distance <= (float) $company->radius_meters);
        } else {
            $distance = 0.0;
            $isWithinRadius = true;
        }

        return [
            'is_within_radius' => $isWithinRadius,
            'distance_meters' => $distance,
            'accuracy_meters' => $accuracy,
            'is_mock_detected' => $isMock,
        ];
    }
}
