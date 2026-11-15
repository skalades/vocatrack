<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Satu-satunya tempat pembuatan tenant sekolah baru beserta pengaturan default
 * dan akun admin awal, dipakai oleh registrasi manual maupun approval demo.
 */
class TenantProvisioningService
{
    public const DEFAULT_SETTINGS = [
        'checkin_start' => '06:00:00',
        'checkin_late' => '08:00:00',
        'checkin_alfa' => '11:00:00',
        'checkout_start' => '16:00:00',
        'journal_backdate_limit' => '3',
        'weight_dudi' => '60',
        'weight_guru' => '40',
        'kop_header_line1' => 'PEMERINTAH PROVINSI',
        'kop_header_line2' => 'DINAS PENDIDIKAN',
        'kepsek_name' => 'Kepala Sekolah',
        'kepsek_nip' => '-',
    ];

    /**
     * Kata sandi sementara acak (tanpa karakter ambigu) untuk akun yang dibuat/direset admin.
     */
    public static function generateTemporaryPassword(int $length = 12): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $max = strlen($alphabet) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }

        return $password;
    }

    /**
     * Buat school + settings default + admin sekolah. Harus dipanggil di dalam transaksi.
     *
     * @return array{0: School, 1: User, 2: string} [school, admin, kata sandi sementara]
     */
    public function provision(array $schoolData, string $adminName, string $adminEmail): array
    {
        $school = School::create(array_merge($schoolData, ['is_active' => true]));

        $settings = self::DEFAULT_SETTINGS + ['kop_header_line3' => Str::upper($school->name)];
        foreach ($settings as $key => $value) {
            SchoolSetting::create([
                'school_id' => $school->id,
                'key' => $key,
                'value' => $value,
            ]);
        }

        $temporaryPassword = self::generateTemporaryPassword();

        $admin = User::create([
            'school_id' => $school->id,
            'name' => $adminName,
            'email' => $adminEmail,
            'password' => $temporaryPassword,
            'role' => 'admin-sekolah',
            'is_active' => true,
            'must_change_password' => true,
        ]);

        return [$school, $admin, $temporaryPassword];
    }
}
