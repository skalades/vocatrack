<?php

namespace Database\Seeders;

use App\Models\Major;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class Smkn4BombanaMajorAndClassSeeder extends Seeder
{
    /**
     * Seed Program Keahlian Geologi Pertambangan SMKN 04 Bombana beserta kelas XII peserta PKL.
     * Geologi Pertambangan adalah kompetensi keahlian SMKN 04 Bombana sejak berdiri (2010), dengan mitra
     * industri tambang nikel di Pulau Kabaena dan sekitarnya.
     */
    public function run(): void
    {
        $school = School::where('npsn', Smkn4BombanaTrialSeeder::NPSN)->firstOrFail();

        $major = Major::firstOrCreate(
            ['school_id' => $school->id, 'code' => 'GP'],
            ['name' => 'Geologi Pertambangan']
        );

        foreach (['XII GP 1', 'XII GP 2'] as $className) {
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
