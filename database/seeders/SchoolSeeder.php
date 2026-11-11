<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Administrator Platform
        $superAdmin = User::create([
            'name' => 'Super Administrator Platform',
            'email' => 'superadmin@vokatrack.id',
            'password' => Hash::make('password'),
            'role' => 'super-admin',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $superAdmin->id,
            'gender' => 'L',
            'phone' => '081234567890',
            'address' => 'Kantor VokaTrack Cloud Platform, Bandung - Jakarta',
        ]);

        // 2. SMKN 2 GARUT (Pionir & Pusat Keunggulan Geologi Pertambangan Jawa Barat)
        $school = School::create([
            'npsn' => '20209203',
            'name' => 'SMK Negeri 2 Garut',
            'address' => 'Jl. Prof. Dr. Suherman No. 90, PO. BOX 103, Kel. Jati, Kec. Tarogong Kaler, Kabupaten Garut, Jawa Barat 44151',
            'phone' => '(0262) 233131',
            'email' => 'info@smkn2garut.sch.id',
            'logo_path' => null,
            'stamp_path' => null,
            'is_active' => true,
        ]);

        // School Settings & Configurations
        $settings = [
            'checkin_start' => '06:00:00',
            'checkin_late' => '08:00:00',
            'checkin_alfa' => '11:00:00',
            'checkout_start' => '16:00:00',
            'journal_backdate_limit' => '3',
            'weight_dudi' => '60',
            'weight_guru' => '40',
            'kop_header_line1' => 'PEMERINTAH DAERAH PROVINSI JAWA BARAT',
            'kop_header_line2' => 'DINAS PENDIDIKAN - CABANG DINAS PENDIDIKAN WILAYAH XI',
            'kop_header_line3' => 'SEKOLAH MENENGAH KEJURUAN NEGERI 2 GARUT',
            'kepsek_name' => 'Nur Al Purkon, S.Pd., M.Si.',
            'kepsek_nip' => '197108251998021004',
        ];

        foreach ($settings as $key => $val) {
            SchoolSetting::create([
                'school_id' => $school->id,
                'key' => $key,
                'value' => $val,
            ]);
        }
    }
}
