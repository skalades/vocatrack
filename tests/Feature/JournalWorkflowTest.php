<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Journal;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_two_tier_approval_lifecycle(): void
    {
        $school = School::create([
            'npsn' => '88888888',
            'name' => 'SMK Journal Test',
            'address' => 'Jakarta',
            'phone' => '021-888',
            'email' => 'smk@journal.sch.id',
            'is_active' => true,
        ]);

        $major = Major::create(['school_id' => $school->id, 'code' => 'RPL', 'name' => 'RPL']);
        $class = SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $major->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $company = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Journal Partner',
            'address' => 'Jakarta',
            'contact_person' => 'PIC',
            'phone' => '0811',
            'work_days' => '5_days',
        ]);

        $period = PklPeriod::create([
            'school_id' => $school->id,
            'name' => 'Periode 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $dudiUser = User::create([
            'school_id' => $school->id,
            'name' => 'Pembimbing DU/DI',
            'email' => 'dudi@partner.com',
            'password' => bcrypt('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        $supervisor = Supervisor::create(['user_id' => $dudiUser->id, 'company_id' => $company->id]);

        $guruUser = User::create([
            'school_id' => $school->id,
            'name' => 'Guru Pembimbing',
            'email' => 'guru@sekolah.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $guruUser->id, 'nip' => '112233']);

        $studentUser = User::create([
            'school_id' => $school->id,
            'name' => 'Siswa Jurnal',
            'email' => 'siswa@sekolah.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'nis' => '501', 'nisn' => '5556667778']);

        $spkl = StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'supervisor_id' => $supervisor->id,
            'status' => 'active',
        ]);

        // 1. Siswa creates journal and submits
        $journal = Journal::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spkl->id,
            'date' => Carbon::today()->toDateString(),
            'activity_description' => 'Mengerjakan instalasi database dan konfigurasi backend API server sesuai arahan.',
            'status' => 'submitted',
        ]);

        $service = new JournalService();

        // 2. Approval Tier 1 by DU/DI: submitted -> review_guru
        $service->approve($journal, $dudiUser, 'Pekerjaan sesuai dengan kebutuhan sprint.');
        $journal->refresh();
        $this->assertEquals('review_guru', $journal->status);
        $this->assertCount(1, $journal->approvals);

        // 3. Approval Tier 2 by Guru: review_guru -> approved
        $service->approve($journal, $guruUser, 'Kompetensi kurikulum tercapai.');
        $journal->refresh();
        $this->assertEquals('approved', $journal->status);
        $this->assertCount(2, $journal->approvals);
    }
}
