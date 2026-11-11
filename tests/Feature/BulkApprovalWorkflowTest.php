<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected User $dudiUser;
    protected Supervisor $supervisor;
    protected Company $company;
    protected StudentPkl $spkl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMK Negeri 1 Test',
            'email' => 'smkn1@test.sch.id',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '021-998877',
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing',
            'email' => 'guru@smkn1test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'school_id' => $this->school->id,
            'nip' => '198001012010011001',
        ]);

        $this->company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Mitra Industri',
            'address' => 'Kawasan Industri',
            'contact_person' => 'Budi Mentor',
            'phone' => '081234567890',
            'work_days' => '5_days',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
        ]);

        $this->dudiUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Mentor DU/DI',
            'email' => 'mentor@mitra.com',
            'password' => bcrypt('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        $this->supervisor = Supervisor::create([
            'user_id' => $this->dudiUser->id,
            'company_id' => $this->company->id,
            'position' => 'Senior Lead',
        ]);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa PKL Test',
            'email' => 'siswa@smkn1test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $major = Major::create(['school_id' => $this->school->id, 'name' => 'RPL', 'code' => 'RPL']);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'major_id' => $major->id, 'name' => 'XII RPL 1', 'grade_level' => 'XII', 'academic_year' => '2026/2027']);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'nisn' => '1234567890', 'nis' => '12345']);

        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Semester Gasal 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $this->company->id,
            'teacher_id' => $this->teacher->id,
            'pkl_period_id' => $period->id,
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);
    }

    public function test_dudi_and_guru_can_bulk_process_journals(): void
    {
        // 1. Create 3 journals submitted by student
        $j1 = Journal::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-08-01',
            'activity_description' => 'Aktivitas Hari 1',
            'status' => 'submitted',
        ]);
        $j2 = Journal::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-08-02',
            'activity_description' => 'Aktivitas Hari 2',
            'status' => 'submitted',
        ]);
        $j3 = Journal::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-08-03',
            'activity_description' => 'Aktivitas Hari 3',
            'status' => 'submitted',
        ]);

        // DU/DI bulk approves j1 and j2
        $this->actingAs($this->dudiUser)
            ->post(route('dudi.journals.bulk'), [
                'journal_ids' => [$j1->id, $j2->id],
                'action' => 'approve',
            ])
            ->assertRedirect();

        $this->assertEquals('review_guru', $j1->fresh()->status);
        $this->assertEquals('review_guru', $j2->fresh()->status);
        $this->assertEquals('submitted', $j3->fresh()->status);

        // DU/DI bulk requests revision for j3
        $this->actingAs($this->dudiUser)
            ->post(route('dudi.journals.bulk'), [
                'journal_ids' => [$j3->id],
                'action' => 'revision',
                'notes' => 'Tolong lengkapi penjelasan modul yang dikerjakan',
            ])
            ->assertRedirect();

        $this->assertEquals('revision', $j3->fresh()->status);

        // Guru bulk approves j1 and j2 (final approved)
        $this->actingAs($this->teacherUser)
            ->post(route('guru.journals.bulk'), [
                'journal_ids' => [$j1->id, $j2->id],
                'action' => 'approve',
            ])
            ->assertRedirect();

        $this->assertEquals('approved', $j1->fresh()->status);
        $this->assertEquals('approved', $j2->fresh()->status);
    }

    public function test_guru_and_dudi_can_bulk_process_attendance_requests(): void
    {
        $req1 = AttendanceRequest::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'type' => 'izin',
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-11',
            'reason' => 'Keperluan keluarga penting',
            'status' => 'pending',
        ]);

        $req2 = AttendanceRequest::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'type' => 'sakit',
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'reason' => 'Demam tinggi dan istirahat',
            'status' => 'pending',
        ]);

        // Guru bulk approves req1 and req2
        $this->actingAs($this->teacherUser)
            ->post(route('guru.requests.bulk'), [
                'request_ids' => [$req1->id, $req2->id],
                'action' => 'approve',
            ])
            ->assertRedirect();

        $this->assertEquals('approved', $req1->fresh()->status);
        $this->assertEquals('approved', $req2->fresh()->status);

        // Verify that attendance records were created for dates
        $this->assertDatabaseHas('attendances', [
            'student_pkl_id' => $this->spkl->id,
            'status' => 'izin',
        ]);
        $this->assertDatabaseHas('attendances', [
            'student_pkl_id' => $this->spkl->id,
            'status' => 'sakit',
        ]);
    }
}
