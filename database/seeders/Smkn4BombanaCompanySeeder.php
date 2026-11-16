<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Major;
use App\Models\Profile;
use App\Models\School;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Smkn4BombanaCompanySeeder extends Seeder
{
    /**
     * Seed Tempat PKL / DUDI pertambangan SMKN 04 Bombana.
     * Nama perusahaan diambil dari daftar mitra DUDI Geologi Pertambangan sekolah di Data Pokok Pendidikan
     * (smk.kemendikdasmen.go.id). Pembimbing DUDI, kontak, dan koordinat GPS adalah data contoh/perkiraan —
     * sesuaikan dengan data riil sebelum dipakai untuk absensi berbasis lokasi.
     */
    public function run(): void
    {
        $school = School::where('npsn', Smkn4BombanaTrialSeeder::NPSN)->firstOrFail();

        $companiesData = [
            [
                'name' => 'PT Wijaya Nikel Nusantara',
                'address' => 'Area Tambang Nikel Pulau Kabaena, Kabupaten Bombana, Sulawesi Tenggara',
                'contact_person' => 'Ir. Abdul Rahman',
                'phone' => '082345671001',
                'latitude' => -5.21500000,
                'longitude' => 121.90500000,
                'radius_meters' => 500,
                'supervisor' => [
                    'name' => 'Ir. Abdul Rahman',
                    'email' => 'spv.wijayanikel@mitra.smkn4bombana.sch.id',
                    'gender' => 'L',
                    'phone' => '082345672001',
                    'position' => 'Mine Geologist / Kepala Seksi Eksplorasi',
                ],
            ],
            [
                'name' => 'PT Nikel Sukses Delapan Delapan',
                'address' => 'Area IUP Nikel Pulau Kabaena, Kabupaten Bombana, Sulawesi Tenggara',
                'contact_person' => 'Syamsul Bahri, S.T.',
                'phone' => '082345671002',
                'latitude' => -5.24000000,
                'longitude' => 121.95000000,
                'radius_meters' => 500,
                'supervisor' => [
                    'name' => 'Syamsul Bahri, S.T.',
                    'email' => 'spv.nsdd@mitra.smkn4bombana.sch.id',
                    'gender' => 'L',
                    'phone' => '082345672002',
                    'position' => 'Supervisor Grade Control & Quality Control Bijih',
                ],
            ],
            [
                'name' => 'PT Aneka Tambang Tbk UBPN Sulawesi Tenggara',
                'address' => 'Unit Bisnis Pertambangan Nikel, Kec. Pomalaa, Kabupaten Kolaka, Sulawesi Tenggara',
                'contact_person' => 'Hendra Kurniawan, S.T.',
                'phone' => '082345671003',
                'latitude' => -4.18500000,
                'longitude' => 121.62000000,
                'radius_meters' => 500,
                'supervisor' => [
                    'name' => 'Hendra Kurniawan, S.T.',
                    'email' => 'spv.antam@mitra.smkn4bombana.sch.id',
                    'gender' => 'L',
                    'phone' => '082345672003',
                    'position' => 'Engineer Perencanaan Tambang',
                ],
            ],
            [
                'name' => 'PT Timah Investasi Mineral',
                'address' => 'Site Eksplorasi Nikel Pulau Kabaena, Kabupaten Bombana, Sulawesi Tenggara',
                'contact_person' => 'Rina Marlina, S.T.',
                'phone' => '082345671004',
                'latitude' => -5.19000000,
                'longitude' => 121.82000000,
                'radius_meters' => 500,
                'supervisor' => [
                    'name' => 'Rina Marlina, S.T.',
                    'email' => 'spv.timahinvest@mitra.smkn4bombana.sch.id',
                    'gender' => 'P',
                    'phone' => '082345672004',
                    'position' => 'Geologist Eksplorasi & Pemetaan',
                ],
            ],
        ];

        $gpId = Major::where('school_id', $school->id)->where('code', 'GP')->value('id');

        foreach ($companiesData as $c) {
            $company = Company::firstOrCreate(
                ['school_id' => $school->id, 'name' => $c['name']],
                [
                    'address' => $c['address'],
                    'contact_person' => $c['contact_person'],
                    'phone' => $c['phone'],
                    'gps_enabled' => true,
                    'latitude' => $c['latitude'],
                    'longitude' => $c['longitude'],
                    'radius_meters' => $c['radius_meters'],
                    'override_hours' => false,
                    'work_days' => '6_days',
                ]
            );

            if ($gpId) {
                $company->majors()->syncWithoutDetaching([$gpId]);
            }

            $spv = $c['supervisor'];
            $spvUser = User::firstOrCreate(
                ['email' => $spv['email']],
                [
                    'school_id' => $school->id,
                    'name' => $spv['name'],
                    'password' => Hash::make('password'),
                    'role' => 'pembimbing-dudi',
                    'is_active' => true,
                ]
            );

            Profile::firstOrCreate(
                ['user_id' => $spvUser->id],
                ['gender' => $spv['gender'], 'phone' => $spv['phone'], 'address' => $c['address']]
            );

            Supervisor::firstOrCreate(
                ['user_id' => $spvUser->id],
                ['company_id' => $company->id, 'position' => $spv['position']]
            );
        }
    }
}
