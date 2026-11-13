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

class Smkn1GarutCompanySeeder extends Seeder
{
    /**
     * Seed 10 Tempat PKL / DUDI Mitra Resmi SMKN 1 Garut lengkap dengan Supervisor DUDI dan Pemetaan Jurusan.
     */
    public function run(): void
    {
        $school = School::where('npsn', '20209201')->firstOrFail();

        $companiesData = [
            [
                'name' => 'Dinas Komunikasi dan Informatika (Diskominfo) Kabupaten Garut',
                'address' => 'Jl. Pembangunan No. 181, Kel. Sukagalih, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Rizal Fahmi, S.T., M.Kom.',
                'phone' => '0262-232145',
                'gps_enabled' => true,
                'latitude' => -7.21880000,
                'longitude' => 107.89670000,
                'radius_meters' => 150,
                'work_days' => '5_days',
                'major_codes' => ['PPLG', 'TJKT', 'MPLB'],
                'supervisor' => [
                    'name' => 'Rizal Fahmi, S.T., M.Kom.',
                    'email' => 'rizal.fahmi@diskominfo.garutkab.go.id',
                    'gender' => 'L',
                    'phone' => '081230001001',
                    'position' => 'Kepala Bidang Infrastruktur & Aplikasi Informatika',
                ],
            ],
            [
                'name' => 'PT Telkom Indonesia (Persero) Tbk - Witel Garut',
                'address' => 'Jl. Pramuka No. 1, Kel. Pakuwon, Kec. Garut Kota, Kabupaten Garut, Jawa Barat 44111',
                'contact_person' => 'Ahmad Fauzi, S.T.',
                'phone' => '0262-231100',
                'gps_enabled' => true,
                'latitude' => -7.21730000,
                'longitude' => 107.90420000,
                'radius_meters' => 120,
                'work_days' => '5_days',
                'major_codes' => ['TJKT', 'PPLG', 'DKV'],
                'supervisor' => [
                    'name' => 'Ahmad Fauzi, S.T.',
                    'email' => 'spv.telkom@telkom.co.id',
                    'gender' => 'L',
                    'phone' => '081230001002',
                    'position' => 'Access Data & Optical Network Section Lead',
                ],
            ],
            [
                'name' => 'PT Bank Pembangunan Daerah Jawa Barat dan Banten Tbk (Bank bjb) Cabang Garut',
                'address' => 'Jl. Ahmad Yani No. 38, Kel. Pakuwon, Kec. Garut Kota, Kabupaten Garut, Jawa Barat 44111',
                'contact_person' => 'Indra Gunawan, S.E.',
                'phone' => '0262-232456',
                'gps_enabled' => true,
                'latitude' => -7.21850000,
                'longitude' => 107.90380000,
                'radius_meters' => 100,
                'work_days' => '5_days',
                'major_codes' => ['AKL', 'MPLB', 'PMS'],
                'supervisor' => [
                    'name' => 'Indra Gunawan, S.E.',
                    'email' => 'spv.bjb@bankbjb.co.id',
                    'gender' => 'L',
                    'phone' => '081230001003',
                    'position' => 'Supervisor Operasional & Layanan Administrasi Perbankan',
                ],
            ],
            [
                'name' => 'PT Akur Pratama (Yogya Group - Yogya Toserba Garut)',
                'address' => 'Jl. Pramuka No. 15, Kel. Pakuwon, Kec. Garut Kota, Kabupaten Garut, Jawa Barat 44111',
                'contact_person' => 'Hendra Suherman, S.E.',
                'phone' => '0262-238890',
                'gps_enabled' => true,
                'latitude' => -7.21680000,
                'longitude' => 107.90550000,
                'radius_meters' => 150,
                'work_days' => '6_days',
                'major_codes' => ['PMS', 'AKL', 'MPLB', 'TLG'],
                'supervisor' => [
                    'name' => 'Hendra Suherman, S.E.',
                    'email' => 'spv.yogya@yogyagroup.com',
                    'gender' => 'L',
                    'phone' => '081230001004',
                    'position' => 'Department Store & Logistics Merchandising Supervisor',
                ],
            ],
            [
                'name' => 'PT Pertamina Geothermal Energy Tbk (PGE) Area Kamojang',
                'address' => 'Kompleks Lapangan Panas Bumi Kamojang, Kec. Samarang, Kabupaten Garut, Jawa Barat 44161',
                'contact_person' => 'Ir. Dwi Prasetyo, M.T.',
                'phone' => '0262-421500',
                'gps_enabled' => true,
                'latitude' => -7.14920000,
                'longitude' => 107.78950000,
                'radius_meters' => 300,
                'work_days' => '5_days',
                'major_codes' => ['TET', 'TLG'],
                'supervisor' => [
                    'name' => 'Ir. Dwi Prasetyo, M.T.',
                    'email' => 'spv.pge@pertamina.com',
                    'gender' => 'L',
                    'phone' => '081230001005',
                    'position' => 'Superintendent Renewable Energy Power Plant & Logistics',
                ],
            ],
            [
                'name' => 'RSUD dr. Slamet Garut',
                'address' => 'Jl. Rumah Sakit No. 12, Kel. Sukakarya, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Apt. Maya Damayanti, S.Farm.',
                'phone' => '0262-232720',
                'gps_enabled' => true,
                'latitude' => -7.21420000,
                'longitude' => 107.89310000,
                'radius_meters' => 200,
                'work_days' => '6_days',
                'major_codes' => ['TF', 'TLM', 'MPLB'],
                'supervisor' => [
                    'name' => 'Apt. Maya Damayanti, S.Farm.',
                    'email' => 'spv.rsudslamet@garutkab.go.id',
                    'gender' => 'P',
                    'phone' => '081230001006',
                    'position' => 'Kepala Instalasi Farmasi Klinis & Penunjang Diagnostik',
                ],
            ],
            [
                'name' => 'Badan Pusat Statistik (BPS) Kabupaten Garut',
                'address' => 'Jl. Pembangunan No. 222, Kel. Sukagalih, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Agus Suryana, S.Si., M.Stat.',
                'phone' => '0262-233481',
                'gps_enabled' => true,
                'latitude' => -7.22150000,
                'longitude' => 107.89480000,
                'radius_meters' => 120,
                'work_days' => '5_days',
                'major_codes' => ['PPLG', 'AKL', 'MPLB'],
                'supervisor' => [
                    'name' => 'Agus Suryana, S.Si., M.Stat.',
                    'email' => 'spv.bps@bps.go.id',
                    'gender' => 'L',
                    'phone' => '081230001007',
                    'position' => 'Koordinator Tim Integrasi Pengolahan & Diseminasi Statistik',
                ],
            ],
            [
                'name' => 'Berdnations Creative Media & Digital Studio',
                'address' => 'Jl. Cimanuk No. 312, Kel. Pataruman, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Gilang Ramadhan, S.Sn.',
                'phone' => '081322119988',
                'gps_enabled' => true,
                'latitude' => -7.22300000,
                'longitude' => 107.90100000,
                'radius_meters' => 100,
                'work_days' => '5_days',
                'major_codes' => ['DKV', 'PPLG'],
                'supervisor' => [
                    'name' => 'Gilang Ramadhan, S.Sn.',
                    'email' => 'spv.berdnations@studio.id',
                    'gender' => 'L',
                    'phone' => '081230001008',
                    'position' => 'Chief Creative Officer & Multimedia Producer',
                ],
            ],
            [
                'name' => 'Kantor Pertanahan / Badan Pertanahan Nasional (BPN) Kabupaten Garut',
                'address' => 'Jl. Prof. Dr. Suherman No. 25, Kel. Jati, Kec. Tarogong Kaler, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Deni Kurniawan, S.H.',
                'phone' => '0262-233150',
                'gps_enabled' => true,
                'latitude' => -7.20250000,
                'longitude' => 107.89200000,
                'radius_meters' => 120,
                'work_days' => '5_days',
                'major_codes' => ['MPLB', 'AKL'],
                'supervisor' => [
                    'name' => 'Deni Kurniawan, S.H.',
                    'email' => 'spv.bpn@atrbpn.go.id',
                    'gender' => 'L',
                    'phone' => '081230001009',
                    'position' => 'Kepala Sub Bagian Tata Usaha & Kearsipan Digital',
                ],
            ],
            [
                'name' => 'PT Kimia Farma Apotek & Diagnostika Garut',
                'address' => 'Jl. Cimanuk No. 22, Kel. Jayawaras, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Apt. Rizki Pratama, S.Farm.',
                'phone' => '0262-233660',
                'gps_enabled' => true,
                'latitude' => -7.22100000,
                'longitude' => 107.90250000,
                'radius_meters' => 100,
                'work_days' => '6_days',
                'major_codes' => ['TF', 'TLM'],
                'supervisor' => [
                    'name' => 'Apt. Rizki Pratama, S.Farm.',
                    'email' => 'spv.kimiafarma@kimiafarma.co.id',
                    'gender' => 'L',
                    'phone' => '081230001010',
                    'position' => 'Apoteker Pengelola Apotek & Unit Layanan Diagnostik',
                ],
            ],
        ];

        foreach ($companiesData as $c) {
            $company = Company::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $c['name'],
                ],
                [
                    'address' => $c['address'],
                    'contact_person' => $c['contact_person'],
                    'phone' => $c['phone'],
                    'gps_enabled' => $c['gps_enabled'],
                    'latitude' => $c['latitude'],
                    'longitude' => $c['longitude'],
                    'radius_meters' => $c['radius_meters'],
                    'override_hours' => false,
                    'work_days' => $c['work_days'],
                ]
            );

            // Hubungkan Relasi DUDI dengan Program Keahlian (company_major)
            $majorIds = Major::where('school_id', $school->id)
                ->whereIn('code', $c['major_codes'])
                ->pluck('id')
                ->toArray();

            if (!empty($majorIds)) {
                $company->majors()->syncWithoutDetaching($majorIds);
            }

            // Buat Akun Supervisor / Pembimbing DUDI
            $spvData = $c['supervisor'];
            $spvUser = User::firstOrCreate(
                ['email' => $spvData['email']],
                [
                    'school_id' => $school->id,
                    'name' => $spvData['name'],
                    'password' => Hash::make('password'),
                    'role' => 'pembimbing-dudi',
                    'is_active' => true,
                ]
            );

            Profile::firstOrCreate(
                ['user_id' => $spvUser->id],
                [
                    'gender' => $spvData['gender'],
                    'phone' => $spvData['phone'],
                    'address' => $c['address'],
                ]
            );

            Supervisor::firstOrCreate(
                ['user_id' => $spvUser->id],
                [
                    'company_id' => $company->id,
                    'position' => $spvData['position'],
                ]
            );
        }
    }
}
