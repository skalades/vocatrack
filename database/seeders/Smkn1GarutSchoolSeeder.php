<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Smkn1GarutSchoolSeeder extends Seeder
{
    /**
     * Seed Sekolah SMKN 1 Garut, Konfigurasi Pengaturan, Admin Sekolah, dan Kepala Sekolah.
     */
    public function run(): void
    {
        // 1. Data Profil Sekolah SMKN 1 Garut
        $school = School::firstOrCreate(
            ['npsn' => '20209201'],
            [
                'name' => 'SMK Negeri 1 Garut',
                'address' => 'Jl. Cimanuk No. 309 A, Kel. Pataruman, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'phone' => '(0262) 233316',
                'email' => 'info@smknegeri1garut.sch.id',
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
            'kop_header_line3' => 'SEKOLAH MENENGAH KEJURUAN NEGERI 1 GARUT',
            'kepsek_name' => 'Dr. H. Asep Rudiana, M.Pd.',
            'kepsek_nip' => '196805121994031008',
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

        // 3. Admin Sekolah SMKN 1 Garut
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@smkn1garut.sch.id'],
            [
                'school_id' => $school->id,
                'name' => 'Admin SMKN 1 Garut',
                'password' => Hash::make('password'),
                'role' => 'admin-sekolah',
                'is_active' => true,
            ]
        );

        Profile::firstOrCreate(
            ['user_id' => $adminUser->id],
            [
                'gender' => 'L',
                'phone' => '081223344601',
                'address' => 'Kampus SMKN 1 Garut, Jl. Cimanuk No. 309 A, Tarogong Kidul, Garut',
            ]
        );

        // 4. Kepala Sekolah SMKN 1 Garut
        $kepsekUser = User::firstOrCreate(
            ['email' => 'kepsek@smkn1garut.sch.id'],
            [
                'school_id' => $school->id,
                'name' => 'Dr. H. Asep Rudiana, M.Pd.',
                'password' => Hash::make('password'),
                'role' => 'kepala-sekolah',
                'is_active' => true,
            ]
        );

        Profile::firstOrCreate(
            ['user_id' => $kepsekUser->id],
            [
                'gender' => 'L',
                'phone' => '081223344602',
                'address' => 'Tarogong Kidul, Kabupaten Garut',
            ]
        );
    }
}
