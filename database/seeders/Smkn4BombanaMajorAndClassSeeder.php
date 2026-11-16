<?php

namespace Database\Seeders;

use App\Models\Major;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class Smkn4BombanaMajorAndClassSeeder extends Seeder
{
    /**
     * Seed 4 Program Keahlian SMKN 04 Bombana beserta kelas tingkat XII.
     */
    public function run(): void
    {
        $school = School::where('npsn', Smkn4BombanaTrialSeeder::NPSN)->firstOrFail();

        $majorsData = [
            ['code' => 'TKRO', 'name' => 'Teknik Kendaraan Ringan Otomotif', 'classes' => ['XII TKRO 1', 'XII TKRO 2']],
            ['code' => 'TJKT', 'name' => 'Teknik Jaringan Komputer dan Telekomunikasi', 'classes' => ['XII TJKT 1']],
            ['code' => 'ATPH', 'name' => 'Agribisnis Tanaman Pangan dan Hortikultura', 'classes' => ['XII ATPH 1']],
            ['code' => 'AKL', 'name' => 'Akuntansi dan Keuangan Lembaga', 'classes' => ['XII AKL 1']],
        ];

        foreach ($majorsData as $mData) {
            $major = Major::firstOrCreate(
                ['school_id' => $school->id, 'code' => $mData['code']],
                ['name' => $mData['name']]
            );

            foreach ($mData['classes'] as $className) {
                SchoolClass::firstOrCreate(
                    ['school_id' => $school->id, 'name' => $className],
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
