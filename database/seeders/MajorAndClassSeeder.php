<?php

namespace Database\Seeders;

use App\Models\Major;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class MajorAndClassSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('npsn', '20209203')->firstOrFail();

        // 1. Majors of SMKN 2 Garut with focus on GEOLOGI PERTAMBANGAN
        $majorGp = Major::create([
            'school_id' => $school->id,
            'code' => 'GP',
            'name' => 'Teknik Geologi Pertambangan',
        ]);

        $majorDpib = Major::create([
            'school_id' => $school->id,
            'code' => 'DPIB',
            'name' => 'Desain Pemodelan dan Informasi Bangunan',
        ]);

        $majorTkr = Major::create([
            'school_id' => $school->id,
            'code' => 'TKR',
            'name' => 'Teknik Kendaraan Ringan Otomotif',
        ]);

        $majorTitl = Major::create([
            'school_id' => $school->id,
            'code' => 'TITL',
            'name' => 'Teknik Instalasi Tenaga Listrik',
        ]);

        $majorTav = Major::create([
            'school_id' => $school->id,
            'code' => 'TAV',
            'name' => 'Teknik Audio Video',
        ]);

        $majorTei = Major::create([
            'school_id' => $school->id,
            'code' => 'TEI',
            'name' => 'Teknik Elektronika Industri',
        ]);

        $majorDkv = Major::create([
            'school_id' => $school->id,
            'code' => 'DKV',
            'name' => 'Desain Komunikasi Visual',
        ]);

        $majorBc = Major::create([
            'school_id' => $school->id,
            'code' => 'BC',
            'name' => 'Broadcasting dan Perfilman',
        ]);

        $majorLog = Major::create([
            'school_id' => $school->id,
            'code' => 'LOG',
            'name' => 'Teknik Logistik',
        ]);

        // 2. Classes (Fokus Jurusan Geologi Pertambangan)
        SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $majorGp->id,
            'name' => 'XII GP 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $majorGp->id,
            'name' => 'XII GP 2',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        // Sample classes for other departments
        SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $majorDpib->id,
            'name' => 'XII DPIB 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $majorTkr->id,
            'name' => 'XII TKR 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $majorTitl->id,
            'name' => 'XII TITL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);
    }
}
