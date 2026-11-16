<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Smkn4BombanaTrialSeeder extends Seeder
{
    /**
     * NPSN resmi SMK Negeri 04 Bombana (Dapodik / smk.kemendikdasmen.go.id).
     * SK Pendirian No. 800/541.a/2010 (27 Okt 2010), akreditasi C (BAN-PDM 2023–2028).
     */
    public const NPSN = '40404977';

    public const KEPSEK_NAME = 'Amir Ishak, S.Pi.';

    /**
     * Tenant SMK Negeri 04 Bombana (Kabaena Barat) dengan paket Trial Evaluasi 7 hari (mulai hari ini):
     * profil sekolah, pengaturan, Admin Sekolah, Kepala Sekolah, dan langganan trial.
     * Data master Geologi Pertambangan ada di seeder Smkn4Bombana* lainnya (lihat Smkn4BombanaSeeder).
     *
     * Jalankan: php artisan db:seed --class=Smkn4BombanaTrialSeeder
     */
    public function run(): void
    {
        // Paket trial harus sudah ada. Jalankan seeder paket lebih dulu bila belum
        // (sebelum sekolah ini dibuat, agar tidak ikut diberi trial "mundur 2 hari").
        if (!SubscriptionPlan::where('code', 'trial-14d')->exists()) {
            $this->call(SubscriptionPlanSeeder::class);
        }
        $trialPlan = SubscriptionPlan::where('code', 'trial-14d')->firstOrFail();

        // 1. Profil Sekolah
        // Sumber: Data Pokok Pendidikan (smk.kemendikdasmen.go.id, sinkron 16 Nov 2026).
        $school = School::updateOrCreate(
            ['npsn' => self::NPSN],
            [
                'name' => 'SMK Negeri 04 Bombana',
                'address' => 'Jl. Bukit Teletubbies, Desa Baliara, Kec. Kabaena Barat, Kabupaten Bombana, Sulawesi Tenggara 93781',
                'phone' => '082291416666',
                'email' => 'smkn.1kabaena@gmail.com',
                'is_active' => true,
            ]
        );

        // 2. Pengaturan Sekolah
        $settings = [
            'checkin_start' => '06:00:00',
            'checkin_late' => '07:30:00',
            'checkin_alfa' => '10:30:00',
            'checkout_start' => '16:00:00',
            'journal_backdate_limit' => '3',
            'weight_dudi' => '60',
            'weight_guru' => '40',
            'kop_header_line1' => 'PEMERINTAH PROVINSI SULAWESI TENGGARA',
            'kop_header_line2' => 'DINAS PENDIDIKAN DAN KEBUDAYAAN',
            'kop_header_line3' => 'SEKOLAH MENENGAH KEJURUAN NEGERI 04 BOMBANA',
            // Sumber: profil sekolah (smk.sch.id & blog resmi sekolah, SK Kepsek No. 28/2021). Verifikasi ke sekolah.
            'kepsek_name' => self::KEPSEK_NAME,
            'kepsek_nip' => '197512222007011006',
        ];

        foreach ($settings as $key => $val) {
            SchoolSetting::updateOrCreate(
                ['school_id' => $school->id, 'key' => $key],
                ['value' => $val]
            );
        }

        // 3. Admin Sekolah
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@smkn4bombana.sch.id'],
            [
                'school_id' => $school->id,
                'name' => 'Admin SMKN 04 Bombana',
                'password' => Hash::make('password'),
                'role' => 'admin-sekolah',
                'is_active' => true,
            ]
        );

        Profile::firstOrCreate(
            ['user_id' => $adminUser->id],
            [
                'gender' => 'L',
                'phone' => null,
                'address' => 'Kampus SMKN 04 Bombana, Jl. Bukit Teletubbies, Baliara, Kabaena Barat, Bombana',
            ]
        );

        // 4. Kepala Sekolah
        $kepsekUser = User::firstOrCreate(
            ['email' => 'kepsek@smkn4bombana.sch.id'],
            [
                'school_id' => $school->id,
                'name' => self::KEPSEK_NAME,
                'password' => Hash::make('password'),
                'role' => 'kepala-sekolah',
                'is_active' => true,
            ]
        );

        Profile::firstOrCreate(
            ['user_id' => $kepsekUser->id],
            [
                'gender' => 'L',
                'phone' => null,
                'address' => 'Kec. Kabaena Barat, Kabupaten Bombana, Sulawesi Tenggara',
            ]
        );

        // 5. Langganan Trial 7 Hari (mulai hari ini). Idempoten: lewati bila sekolah sudah punya periode.
        if (!$school->subscriptions()->exists()) {
            app(SubscriptionService::class)->subscribeSchool(
                $school,
                $trialPlan,
                Carbon::today(),
                null, // kuota siswa mengikuti paket trial (50)
                0,
                'trial',
                "Paket Trial Evaluasi Sistem (" . SubscriptionService::TRIAL_DAYS . " Hari) — Tenant {$school->name}"
            );
        }
    }
}
