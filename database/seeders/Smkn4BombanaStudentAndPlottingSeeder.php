<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PklPeriod;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Smkn4BombanaStudentAndPlottingSeeder extends Seeder
{
    /**
     * Seed siswa SMKN 04 Bombana (data contoh, masih di bawah kuota trial 50 siswa)
     * beserta periode PKL aktif dan plotting penempatan.
     */
    public function run(): void
    {
        $school = School::where('npsn', Smkn4BombanaTrialSeeder::NPSN)->firstOrFail();

        $period = PklPeriod::firstOrCreate(
            [
                'school_id' => $school->id,
                'name' => 'PKL SMKN 04 Bombana TP 2026/2027',
            ],
            [
                'start_date' => Carbon::today()->startOfMonth()->toDateString(),
                'end_date' => Carbon::today()->addMonths(3)->endOfMonth()->toDateString(),
                'is_active' => true,
            ]
        );

        // [nama, email, nis, nisn, gender, kelas, kata kunci DUDI, email guru]
        $studentsData = [
            ['Muhammad Rizal', 'rizal.tkro@smkn4bombana.sch.id', '262701', '0082700001', 'L', 'XII TKRO 1', 'Sinar Rumbia', 'arif.tkro@smkn4bombana.sch.id'],
            ['Ahmad Fauzan', 'fauzan.tkro@smkn4bombana.sch.id', '262702', '0082700002', 'L', 'XII TKRO 1', 'Sinar Rumbia', 'arif.tkro@smkn4bombana.sch.id'],
            ['La Ode Ikhsan', 'ikhsan.tkro@smkn4bombana.sch.id', '262703', '0082700003', 'L', 'XII TKRO 2', 'Jaya Kasipute', 'arif.tkro@smkn4bombana.sch.id'],
            ['Arman Saputra', 'arman.tkro@smkn4bombana.sch.id', '262704', '0082700004', 'L', 'XII TKRO 2', 'Jaya Kasipute', 'arif.tkro@smkn4bombana.sch.id'],
            ['Nur Afni', 'afni.tjkt@smkn4bombana.sch.id', '262705', '0082700005', 'P', 'XII TJKT 1', 'Komunikasi dan Informatika', 'andi.tjkt@smkn4bombana.sch.id'],
            ['Fikri Ramadhan', 'fikri.tjkt@smkn4bombana.sch.id', '262706', '0082700006', 'L', 'XII TJKT 1', 'Komunikasi dan Informatika', 'andi.tjkt@smkn4bombana.sch.id'],
            ['Wa Ode Sitti Aisyah', 'aisyah.atph@smkn4bombana.sch.id', '262707', '0082700007', 'P', 'XII ATPH 1', 'Pertanian', 'nurhayati.atph@smkn4bombana.sch.id'],
            ['Haerul Anwar', 'haerul.atph@smkn4bombana.sch.id', '262708', '0082700008', 'L', 'XII ATPH 1', 'Pertanian', 'nurhayati.atph@smkn4bombana.sch.id'],
            ['Risna Wati', 'risna.akl@smkn4bombana.sch.id', '262709', '0082700009', 'P', 'XII AKL 1', 'Koperasi', 'rahmawati.akl@smkn4bombana.sch.id'],
            ['Dian Pratiwi', 'dian.akl@smkn4bombana.sch.id', '262710', '0082700010', 'P', 'XII AKL 1', 'Koperasi', 'rahmawati.akl@smkn4bombana.sch.id'],
        ];

        foreach ($studentsData as $i => [$name, $email, $nis, $nisn, $gender, $className, $companyKeyword, $teacherEmail]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'school_id' => $school->id,
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => 'siswa',
                    'is_active' => true,
                ]
            );

            Profile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'gender' => $gender,
                    'phone' => '0823456780' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'address' => 'Kec. Rumbia, Kabupaten Bombana, Sulawesi Tenggara',
                ]
            );

            $class = SchoolClass::where('school_id', $school->id)->where('name', $className)->firstOrFail();

            $student = Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'class_id' => $class->id,
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'parent_token' => Str::random(32),
                    'is_parent_portal_active' => true,
                ]
            );

            $company = Company::where('school_id', $school->id)
                ->where('name', 'like', '%' . $companyKeyword . '%')
                ->firstOrFail();

            $supervisor = Supervisor::where('company_id', $company->id)->first();
            $teacher = Teacher::whereHas('user', fn ($q) => $q->where('email', $teacherEmail))->firstOrFail();

            StudentPkl::updateOrCreate(
                ['student_id' => $student->id, 'pkl_period_id' => $period->id],
                [
                    'school_id' => $school->id,
                    'company_id' => $company->id,
                    'teacher_id' => $teacher->id,
                    'supervisor_id' => $supervisor?->id,
                    'status' => 'active',
                ]
            );
        }
    }
}
