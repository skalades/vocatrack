<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use Database\Seeders\GeologiPertambanganPklSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeologiPertambanganPklSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_gp_seeder_matches_live_data_and_is_idempotent(): void
    {
        $school = School::create([
            'npsn' => '20209203',
            'name' => 'SMK Negeri 2 Garut',
            'address' => 'Jl. Prof. Dr. Suherman No. 90, Garut',
            'phone' => '(0262) 233131',
            'email' => 'info@smkn2garut.sch.id',
            'is_active' => true,
        ]);

        $this->seed(GeologiPertambanganPklSeeder::class);
        $this->seed(GeologiPertambanganPklSeeder::class);

        $this->assertEquals(68, Student::count());
        $this->assertEquals(68, StudentPkl::count());

        $class1 = SchoolClass::where('school_id', $school->id)->where('name', 'XII GP 1')->firstOrFail();
        $class2 = SchoolClass::where('school_id', $school->id)->where('name', 'XII GP 2')->firstOrFail();
        $this->assertEquals('Erika Silviani, S.T.', $class1->homeroomTeacher->user->name);
        $this->assertEquals('Nisa Dwi Nuranti, S.Pd.', $class2->homeroomTeacher->user->name);

        $fani = Student::where('nis', '242510507')->firstOrFail();
        $this->assertEquals('08886008478', $fani->user->wa_number);
        $this->assertEquals('ATR BPN', $fani->activePkl->company->name);
        $this->assertEquals('Danang Kusumah, S.T.', $fani->activePkl->teacher->user->name);

        $nazil = Student::where('nis', '242510526')->firstOrFail();
        $this->assertEquals('PT. ARYA SWASCIPTA REKAYASA/PT. MAHATMA', $nazil->activePkl->company->name);
        $this->assertEquals('Yayu Sri Oktaviani, S.Pd.', $nazil->activePkl->teacher->user->name);

        $nazmy = Student::where('nis', '242510489')->firstOrFail();
        $this->assertNull($nazmy->user->wa_number);
        $this->assertEquals('PT. Timah Persero, Tbk.', $nazmy->activePkl->company->name);
        $this->assertEquals('Hj. Aida Maryani S, S.Pd., M.Pd.', $nazmy->activePkl->teacher->user->name);

        // Hanya PT. DATEM dan PT. SANGGA BUANA NUSANTARA yang berstatus sudah mulai PKL
        $startedNames = Company::where('school_id', $school->id)
            ->where('is_pkl_started', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $this->assertSame(['PT. DATEM', 'PT. SANGGA BUANA NUSANTARA'], $startedNames);
    }
}
