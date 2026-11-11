<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PklPeriodAndPlottingSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('npsn', '20209203')->firstOrFail();

        // 1. PKL Period for SMKN 2 Garut
        $period = PklPeriod::create([
            'school_id' => $school->id,
            'name' => 'PKL Eksplorasi Industri Pertambangan & Geologi TP 2026/2027',
            'start_date' => Carbon::now()->subMonths(1)->startOfMonth()->toDateString(),
            'end_date' => Carbon::now()->addMonths(4)->endOfMonth()->toDateString(),
            'is_active' => true,
        ]);

        // Teachers
        $teacher1 = User::where('email', 'guru@smkn2garut.sch.id')->firstOrFail()->teacher;
        $teacher2 = User::where('email', 'guru2@smkn2garut.sch.id')->firstOrFail()->teacher;

        // Companies & Supervisors
        $antam = Company::where('school_id', $school->id)->where('name', 'like', '%Aneka Tambang%')->firstOrFail();
        $supervisorAntam = Supervisor::where('company_id', $antam->id)->firstOrFail();

        $timah = Company::where('school_id', $school->id)->where('name', 'like', '%Timah%')->firstOrFail();
        $supervisorTimah = Supervisor::where('company_id', $timah->id)->firstOrFail();

        $geologi = Company::where('school_id', $school->id)->where('name', 'like', '%PSDMBP%')->firstOrFail();
        $supervisorGeologi = Supervisor::where('company_id', $geologi->id)->firstOrFail();

        $geoservices = Company::where('school_id', $school->id)->where('name', 'like', '%Geoservices%')->firstOrFail();
        $supervisorGeoservices = Supervisor::where('company_id', $geoservices->id)->firstOrFail();

        // Students
        $student1 = User::where('email', 'siswa@smkn2garut.sch.id')->firstOrFail()->student;
        $student2 = User::where('email', 'rendi@smkn2garut.sch.id')->firstOrFail()->student;
        $student3 = User::where('email', 'gina@smkn2garut.sch.id')->firstOrFail()->student;
        $student4 = User::where('email', 'dika@smkn2garut.sch.id')->firstOrFail()->student;

        // 2. Plottings (Penempatan Siswa Geologi Pertambangan)
        // Fajar Nugraha -> PT Aneka Tambang Tbk (Antam - Geomin)
        StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student1->id,
            'company_id' => $antam->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher1->id,
            'supervisor_id' => $supervisorAntam->id,
            'status' => 'active',
        ]);

        // Rendi Pratama -> PT Timah Tbk
        StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student2->id,
            'company_id' => $timah->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher1->id,
            'supervisor_id' => $supervisorTimah->id,
            'status' => 'active',
        ]);

        // Gina Septiani -> PSDMBP Badan Geologi ESDM
        StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student3->id,
            'company_id' => $geologi->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher2->id,
            'supervisor_id' => $supervisorGeologi->id,
            'status' => 'active',
        ]);

        // Dika Firmansyah -> PT Geoservices Ltd.
        StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student4->id,
            'company_id' => $geoservices->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher2->id,
            'supervisor_id' => $supervisorGeoservices->id,
            'status' => 'active',
        ]);
    }
}
