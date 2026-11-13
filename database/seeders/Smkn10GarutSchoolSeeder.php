<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Smkn10GarutSchoolSeeder extends Seeder
{
    /**
     * Seed Profil Sekolah SMKN 10 Garut, Pengaturan, Admin Sekolah, dan Kepala Sekolah.
     */
    public function run(): void
    {
        // 1. Data Profil Sekolah SMKN 10 Garut
        $school = School::firstOrCreate(
            ['npsn' => '20262128'],
            [
                'name' => 'SMK Negeri 10 Garut',
                'address' => 'Jl. Raya Garut - Tasikmalaya Km. 08, Desa Cilawu, Kec. Cilawu, Kabupaten Garut, Jawa Barat 44181',
                'phone' => '(0262) 4891303',
                'email' => 'info@smknegeri10garut.sch.id',
                'logo_path' => null,
                'stamp_path' => null,
                'is_active' => true,
            ]
        );

        // 2. School Settings & Configurations
        $settings = [
            'checkin_start' => '06:00:00',
            'checkin_late' => '07:30:00',
            'checkin_alfa' => '10:30:00',
            'checkout_start' => '16:00:00',
            'journal_backdate_limit' => '3',
            'weight_dudi' => '60',
            'weight_guru' => '40',
            'kop_header_line1' => 'PEMERINTAH DAERAH PROVINSI JAWA BARAT',
            'kop_header_line2' => 'DINAS PENDIDIKAN - CABANG DINAS PENDIDIKAN WILAYAH XI',
            'kop_header_line3' => 'SEKOLAH MENENGAH KEJURUAN NEGERI 10 GARUT',
            'kepsek_name' => 'Hj. Enden Lesmanawati, S.Pd., M.Pd.',
            'kepsek_nip' => '197004181997022002',
        ];

        foreach ($settings as $key => $val) {
            SchoolSetting::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'key' => $key,
                ],
                [
                    'value' => $val,
                ]
            );
        }

        // 3. Admin Sekolah SMKN 10 Garut
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@smkn10garut.sch.id'],
            [
                'school_id' => $school->id,
                'name' => 'Admin SMKN 10 Garut',
                'password' => Hash::make('password'),
                'role' => 'admin-sekolah',
                'is_active' => true,
            ]
        );

        Profile::firstOrCreate(
            ['user_id' => $adminUser->id],
            [
                'gender' => 'L',
                'phone' => '081223344701',
                'address' => 'Kampus SMKN 10 Garut, Jl. Raya Garut-Tasik Km. 08, Cilawu, Garut',
            ]
        );

        // 4. Kepala Sekolah SMKN 10 Garut
        $kepsekUser = User::firstOrCreate(
            ['email' => 'kepsek@smkn10garut.sch.id'],
            [
                'school_id' => $school->id,
                'name' => 'Hj. Enden Lesmanawati, S.Pd., M.Pd.',
                'password' => Hash::make('password'),
                'role' => 'kepala-sekolah',
                'is_active' => true,
            ]
        );

        Profile::firstOrCreate(
            ['user_id' => $kepsekUser->id],
            [
                'gender' => 'P',
                'phone' => '081223344702',
                'address' => 'Kecamatan Cilawu, Kabupaten Garut',
            ]
        );
    }
}
