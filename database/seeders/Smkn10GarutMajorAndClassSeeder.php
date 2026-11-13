<?php

namespace Database\Seeders;

use App\Models\Major;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class Smkn10GarutMajorAndClassSeeder extends Seeder
{
    /**
     * Seed 6 Program Keahlian Unggulan SMKN 10 Garut beserta Kelas-kelas Tingkat XII.
     */
    public function run(): void
    {
        $school = School::where('npsn', '20262128')->firstOrFail();

        $majorsData = [
            [
                'code' => 'TKRO',
                'name' => 'Teknik Kendaraan Ringan Otomotif',
                'classes' => ['XII TKRO 1', 'XII TKRO 2'],
            ],
            [
                'code' => 'TBSM',
                'name' => 'Teknik dan Bisnis Sepeda Motor',
                'classes' => ['XII TBSM 1', 'XII TBSM 2'],
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
                'code' => 'LPS',
                'name' => 'Layanan Perbankan Syariah',
                'classes' => ['XII LPS 1', 'XII LPS 2'],
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
