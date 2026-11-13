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

class Smkn10GarutCompanySeeder extends Seeder
{
    /**
     * Seed 10 Tempat PKL / DUDI Mitra Resmi SMKN 10 Garut lengkap dengan Supervisor DUDI dan Pemetaan Jurusan.
     */
    public function run(): void
    {
        $school = School::where('npsn', '20262128')->firstOrFail();

        $companiesData = [
            [
                'name' => 'Bengkel Resmi Auto2000 Garut (PT Astra International Tbk - Toyota)',
                'address' => 'Jl. Prof. Dr. Suherman No. 67, Kel. Jati, Kec. Tarogong Kaler, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Bambang Irawan, S.T.',
                'phone' => '0262-237000',
                'gps_enabled' => true,
                'latitude' => -7.20650000,
                'longitude' => 107.89320000,
                'radius_meters' => 150,
                'work_days' => '6_days',
                'major_codes' => ['TKRO'],
                'supervisor' => [
                    'name' => 'Bambang Irawan, S.T.',
                    'email' => 'spv.auto2000@toyota.astra.co.id',
                    'gender' => 'L',
                    'phone' => '081230002001',
                    'position' => 'Service Manager & Kepala Bengkel Resmi Toyota Auto2000',
                ],
            ],
            [
                'name' => 'Bengkel AHASS Daya Motor Garut (PT Daya Adicipta Motora - Honda)',
                'address' => 'Jl. Cimanuk No. 250, Kel. Pataruman, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Agus Setiawan',
                'phone' => '0262-234888',
                'gps_enabled' => true,
                'latitude' => -7.21950000,
                'longitude' => 107.90210000,
                'radius_meters' => 120,
                'work_days' => '6_days',
                'major_codes' => ['TBSM'],
                'supervisor' => [
                    'name' => 'Agus Setiawan',
                    'email' => 'spv.ahass@hondamotor.co.id',
                    'gender' => 'L',
                    'phone' => '081230002002',
                    'position' => 'Kepala Bengkel Resmi AHASS Daya Motor Honda',
                ],
            ],
            [
                'name' => 'Bengkel Bratayudha Motor Garut',
                'address' => 'Jl. Bratayuda No. 85, Kel. Kota Kulon, Kec. Garut Kota, Kabupaten Garut, Jawa Barat 44112',
                'contact_person' => 'Dadang Hidayat',
                'phone' => '0262-231900',
                'gps_enabled' => true,
                'latitude' => -7.21780000,
                'longitude' => 107.90950000,
                'radius_meters' => 100,
                'work_days' => '6_days',
                'major_codes' => ['TKRO', 'TBSM'],
                'supervisor' => [
                    'name' => 'Dadang Hidayat',
                    'email' => 'spv.bratayudha@bengkel.id',
                    'gender' => 'L',
                    'phone' => '081230002003',
                    'position' => 'Managing Director & Kepala Mekanik Otomotif',
                ],
            ],
            [
                'name' => 'PT Bank Syariah Indonesia Tbk (BSI) KCP Garut',
                'address' => 'Jl. Ahmad Yani No. 88, Kel. Pakuwon, Kec. Garut Kota, Kabupaten Garut, Jawa Barat 44111',
                'contact_person' => 'Muhammad Ridwan, S.E.I.',
                'phone' => '0262-232100',
                'gps_enabled' => true,
                'latitude' => -7.21820000,
                'longitude' => 107.90450000,
                'radius_meters' => 100,
                'work_days' => '5_days',
                'major_codes' => ['LPS', 'AKL'],
                'supervisor' => [
                    'name' => 'Muhammad Ridwan, S.E.I.',
                    'email' => 'spv.bsi@bankbsi.co.id',
                    'gender' => 'L',
                    'phone' => '081230002004',
                    'position' => 'Branch Operations & Service Supervisor BSI Garut',
                ],
            ],
            [
                'name' => 'PT Telkom Indonesia (Persero) Tbk - Unit Jaringan Garut',
                'address' => 'Jl. Pramuka No. 1, Kel. Pakuwon, Kec. Garut Kota, Kabupaten Garut, Jawa Barat 44111',
                'contact_person' => 'Dani Ramdani, S.T.',
                'phone' => '0262-231100',
                'gps_enabled' => true,
                'latitude' => -7.21730000,
                'longitude' => 107.90420000,
                'radius_meters' => 120,
                'work_days' => '5_days',
                'major_codes' => ['TJKT', 'DKV'],
                'supervisor' => [
                    'name' => 'Dani Ramdani, S.T.',
                    'email' => 'spv.telkom.cilawu@telkom.co.id',
                    'gender' => 'L',
                    'phone' => '081230002005',
                    'position' => 'Senior Field Engineer & Akses Jaringan Optik Telkom',
                ],
            ],
            [
                'name' => 'Balai Benih Perkebunan Dinas Pertanian Kabupaten Garut',
                'address' => 'Jl. Raya Garut - Tasikmalaya Km. 6.5, Desa Cilawu, Kec. Cilawu, Kabupaten Garut, Jawa Barat 44181',
                'contact_person' => 'Ir. Cecep Suhendar',
                'phone' => '0262-489020',
                'gps_enabled' => true,
                'latitude' => -7.25100000,
                'longitude' => 107.92500000,
                'radius_meters' => 200,
                'work_days' => '5_days',
                'major_codes' => ['TJKT', 'AKL', 'LPS'],
                'supervisor' => [
                    'name' => 'Ir. Cecep Suhendar',
                    'email' => 'spv.balaibenih@garutkab.go.id',
                    'gender' => 'L',
                    'phone' => '081230002006',
                    'position' => 'Kepala Sub Bagian Tata Usaha & IT Balai Benih',
                ],
            ],
            [
                'name' => 'Dinas Komunikasi dan Informatika (Diskominfo) Kabupaten Garut - Divisi Jaringan',
                'address' => 'Jl. Pembangunan No. 181, Kel. Sukagalih, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Pebri Abdul Munawar',
                'phone' => '0262-232145',
                'gps_enabled' => true,
                'latitude' => -7.21880000,
                'longitude' => 107.89670000,
                'radius_meters' => 150,
                'work_days' => '5_days',
                'major_codes' => ['TJKT', 'DKV'],
                'supervisor' => [
                    'name' => 'Pebri Abdul Munawar',
                    'email' => 'pebri.munawar@diskominfo.garutkab.go.id',
                    'gender' => 'L',
                    'phone' => '081230002007',
                    'position' => 'Pranata Komputer & Supervisor Jaringan Diskominfo Garut',
                ],
            ],
            [
                'name' => 'Bank Perkreditan Rakyat Syariah (BPRS) Garut',
                'address' => 'Jl. Cimanuk No. 115, Kel. Pataruman, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat 44151',
                'contact_person' => 'Siti Hanifah, S.E.Sy.',
                'phone' => '0262-238450',
                'gps_enabled' => true,
                'latitude' => -7.22050000,
                'longitude' => 107.90150000,
                'radius_meters' => 100,
                'work_days' => '5_days',
                'major_codes' => ['LPS', 'AKL'],
                'supervisor' => [
                    'name' => 'Siti Hanifah, S.E.Sy.',
                    'email' => 'spv.bprs@bprsgarut.co.id',
                    'gender' => 'P',
                    'phone' => '081230002008',
                    'position' => 'Kepala Bagian Operasional & Pembiayaan Syariah',
                ],
            ],
            [
                'name' => 'Percetakan & Digital Printing Duta Media Garut',
                'address' => 'Jl. Raya Garut - Tasikmalaya Km. 06, Desa Cilawu, Kec. Cilawu, Kabupaten Garut, Jawa Barat 44181',
                'contact_person' => 'Yayan Mulyana, S.Sn.',
                'phone' => '081223908877',
                'gps_enabled' => true,
                'latitude' => -7.24850000,
                'longitude' => 107.92100000,
                'radius_meters' => 100,
                'work_days' => '6_days',
                'major_codes' => ['DKV'],
                'supervisor' => [
                    'name' => 'Yayan Mulyana, S.Sn.',
                    'email' => 'spv.dutamedia@percetakan.id',
                    'gender' => 'L',
                    'phone' => '081230002009',
                    'position' => 'Kepala Divisi Desain Grafis & Produksi Cetak Digital',
                ],
            ],
            [
                'name' => 'CV Sinar Otomotif Mandiri Cilawu',
                'address' => 'Jl. Raya Garut - Tasikmalaya Km. 8.5, Desa Cilawu, Kec. Cilawu, Kabupaten Garut, Jawa Barat 44181',
                'contact_person' => 'Wawan Gunawan',
                'phone' => '081324556677',
                'gps_enabled' => true,
                'latitude' => -7.25500000,
                'longitude' => 107.93100000,
                'radius_meters' => 120,
                'work_days' => '6_days',
                'major_codes' => ['TKRO', 'TBSM'],
                'supervisor' => [
                    'name' => 'Wawan Gunawan',
                    'email' => 'spv.sinarotomotif@bengkel.id',
                    'gender' => 'L',
                    'phone' => '081230002010',
                    'position' => 'Kepala Bengkel Perawatan & Perbaikan Kendaraan Bermotor',
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
