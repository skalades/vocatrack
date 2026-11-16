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
     * Seed Tempat PKL / DUDI mitra SMKN 04 Bombana (data contoh) beserta Pembimbing DUDI dan pemetaan jurusan.
     */
    public function run(): void
    {
        $school = School::where('npsn', Smkn4BombanaTrialSeeder::NPSN)->firstOrFail();

        $companiesData = [
            [
                'name' => 'Bengkel Mobil Sinar Rumbia',
                'address' => 'Jl. Poros Kasipute - Kendari, Kel. Kasipute, Kec. Rumbia, Kabupaten Bombana',
                'contact_person' => 'Hasanuddin',
                'phone' => '082345671001',
                'latitude' => -4.85620000,
                'longitude' => 121.94810000,
                'radius_meters' => 120,
                'work_days' => '6_days',
                'major_codes' => ['TKRO'],
                'supervisor' => [
                    'name' => 'Hasanuddin',
                    'email' => 'spv.sinarrumbia@dudi-bombana.id',
                    'gender' => 'L',
                    'phone' => '082345672001',
                    'position' => 'Kepala Bengkel & Mekanik Senior',
                ],
            ],
            [
                'name' => 'Bengkel Motor Jaya Kasipute',
                'address' => 'Jl. Pendidikan, Kel. Kasipute, Kec. Rumbia, Kabupaten Bombana',
                'contact_person' => 'Muhammad Yusuf',
                'phone' => '082345671002',
                'latitude' => -4.85310000,
                'longitude' => 121.95120000,
                'radius_meters' => 100,
                'work_days' => '6_days',
                'major_codes' => ['TKRO'],
                'supervisor' => [
                    'name' => 'Muhammad Yusuf',
                    'email' => 'spv.jayakasipute@dudi-bombana.id',
                    'gender' => 'L',
                    'phone' => '082345672002',
                    'position' => 'Pemilik & Kepala Mekanik',
                ],
            ],
            [
                'name' => 'Dinas Komunikasi dan Informatika Kabupaten Bombana',
                'address' => 'Kompleks Perkantoran Pemda Bombana, Kec. Rumbia, Kabupaten Bombana',
                'contact_person' => 'Irwan Saputra, S.Kom.',
                'phone' => '082345671003',
                'latitude' => -4.85950000,
                'longitude' => 121.94400000,
                'radius_meters' => 150,
                'work_days' => '5_days',
                'major_codes' => ['TJKT'],
                'supervisor' => [
                    'name' => 'Irwan Saputra, S.Kom.',
                    'email' => 'spv.diskominfo@dudi-bombana.id',
                    'gender' => 'L',
                    'phone' => '082345672003',
                    'position' => 'Pranata Komputer - Bidang Infrastruktur Jaringan',
                ],
            ],
            [
                'name' => 'Dinas Pertanian dan Peternakan Kabupaten Bombana',
                'address' => 'Kompleks Perkantoran Pemda Bombana, Kec. Rumbia, Kabupaten Bombana',
                'contact_person' => 'Ir. Rusdin',
                'phone' => '082345671004',
                'latitude' => -4.86010000,
                'longitude' => 121.94550000,
                'radius_meters' => 150,
                'work_days' => '5_days',
                'major_codes' => ['ATPH'],
                'supervisor' => [
                    'name' => 'Ir. Rusdin',
                    'email' => 'spv.distan@dudi-bombana.id',
                    'gender' => 'L',
                    'phone' => '082345672004',
                    'position' => 'Penyuluh Pertanian Lapangan',
                ],
            ],
            [
                'name' => 'Koperasi Simpan Pinjam Sejahtera Bombana',
                'address' => 'Jl. Poros Kasipute, Kel. Lameroro, Kec. Rumbia, Kabupaten Bombana',
                'contact_person' => 'Nurlina, S.E.',
                'phone' => '082345671005',
                'latitude' => -4.85480000,
                'longitude' => 121.94990000,
                'radius_meters' => 100,
                'work_days' => '5_days',
                'major_codes' => ['AKL'],
                'supervisor' => [
                    'name' => 'Nurlina, S.E.',
                    'email' => 'spv.ksp@dudi-bombana.id',
                    'gender' => 'P',
                    'phone' => '082345672005',
                    'position' => 'Kepala Bagian Keuangan',
                ],
            ],
        ];

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
                    'work_days' => $c['work_days'],
                ]
            );

            $majorIds = Major::where('school_id', $school->id)
                ->whereIn('code', $c['major_codes'])
                ->pluck('id')
                ->toArray();

            if (!empty($majorIds)) {
                $company->majors()->syncWithoutDetaching($majorIds);
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
