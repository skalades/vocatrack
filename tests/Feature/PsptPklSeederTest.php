<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use Database\Seeders\PsptPklSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsptPklSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_pspt_seeder_matches_live_data_and_is_idempotent(): void
    {
        $school = School::create([
            'npsn' => '20209203',
            'name' => 'SMK Negeri 2 Garut',
            'address' => 'Jl. Prof. Dr. Suherman No. 90, Garut',
            'phone' => '(0262) 233131',
            'email' => 'info@smkn2garut.sch.id',
            'is_active' => true,
        ]);

        $this->seed(PsptPklSeeder::class);
        $this->seed(PsptPklSeeder::class);

        $this->assertEquals(68, Student::count());
        $this->assertEquals(65, StudentPkl::count());

        $class1 = SchoolClass::where('school_id', $school->id)->where('name', 'XII PSPT 1')->firstOrFail();
        $class2 = SchoolClass::where('school_id', $school->id)->where('name', 'XII PSPT 2')->firstOrFail();
        $this->assertEquals('Asep Suhendra, S.Pd., M.Pd.', $class1->homeroomTeacher->user->name);
        $this->assertEquals('Ridwan Setiawan Darajat, S.Sos., M.I.Kom', $class2->homeroomTeacher->user->name);

        $abrar = Student::where('nis', '242510610')->firstOrFail();
        $this->assertEquals('083840023155', $abrar->user->wa_number);
        $this->assertEquals('TVRI JAWA BARAT', $abrar->activePkl->company->name);
        $this->assertEquals('Fitries Erinda Hapsari, S.ST', $abrar->activePkl->teacher->user->name);

        $hasna = Student::where('nis', '242510658')->firstOrFail();
        $this->assertEquals('PENGADILAN AGAMA KAB GARUT', $hasna->activePkl->company->name);
        $this->assertEquals('Rini Anggraini, S.Pd.I', $hasna->activePkl->teacher->user->name);

        $syahrul = Student::where('nis', '242510643')->firstOrFail();
        $this->assertNull($syahrul->activePkl);
    }
}
