<?php

namespace Database\Seeders;

use App\Models\Major;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class Smkn1GarutMajorAndClassSeeder extends Seeder
{
    /**
     * Seed 10 Jurusan Resmi SMKN 1 Garut beserta Kelas-kelas Tingkat XII.
     */
    public function run(): void
    {
        $school = School::where('npsn', '20209201')->firstOrFail();

        // Daftar 10 Jurusan Resmi SMKN 1 Garut sesuai Kurikulum Terkini
        $majorsData = [
            [
                'code' => 'PPLG',
                'name' => 'Pengembangan Perangkat Lunak dan Gim',
                'classes' => ['XII PPLG 1', 'XII PPLG 2'],
            ],
            [
                'code' => 'TJKT',
                'name' => 'Teknik Jaringan Komputer dan Telekomunikasi',
                'classes' => ['XII TJKT 1', 'XII TJKT 2'],
            ],
            [
                'code' => 'DKV',
                'name' => 'Desain Komunikasi Visual',
                'classes' => ['XII DKV 1', 'XII DKV 2'],
            ],
            [
                'code' => 'AKL',
                'name' => 'Akuntansi dan Keuangan Lembaga',
                'classes' => ['XII AKL 1', 'XII AKL 2'],
            ],
            [
                'code' => 'MPLB',
                'name' => 'Manajemen Perkantoran dan Layanan Bisnis',
                'classes' => ['XII MPLB 1', 'XII MPLB 2'],
            ],
            [
                'code' => 'PMS',
                'name' => 'Pemasaran',
                'classes' => ['XII PMS 1', 'XII PMS 2'],
            ],
            [
                'code' => 'TET',
                'name' => 'Teknik Energi Terbarukan',
                'classes' => ['XII TET 1'],
            ],
            [
                'code' => 'TLM',
                'name' => 'Teknik Laboratorium Medik',
                'classes' => ['XII TLM 1'],
            ],
            [
                'code' => 'TF',
                'name' => 'Teknologi Farmasi',
                'classes' => ['XII TF 1'],
            ],
            [
                'code' => 'TLG',
                'name' => 'Teknik Logistik',
                'classes' => ['XII TLG 1'],
            ],
        ];

        foreach ($majorsData as $mData) {
            $major = Major::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'code' => $mData['code'],
                ],
                [
                    'name' => $mData['name'],
                ]
            );

            foreach ($mData['classes'] as $className) {
                SchoolClass::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'name' => $className,
                    ],
                    [
                        'major_id' => $major->id,
                        'grade_level' => 'XII',
                        'academic_year' => '2026/2027',
                    ]
                );
            }
        }
    }
}
