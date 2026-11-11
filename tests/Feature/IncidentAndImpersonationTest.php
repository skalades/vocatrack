<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ImpersonationService;
use App\Services\IncidentReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IncidentAndImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected School $otherSchool;
    protected User $adminUser;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected User $dudiUser;
    protected Supervisor $supervisor;
    protected Company $company;
    protected User $studentUser;
    protected Student $student;
    protected StudentPkl $spkl;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMK Negeri 1 Test',
            'email' => 'smkn1@test.sch.id',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '021-998877',
            'is_active' => true,
        ]);

        $this->otherSchool = School::create([
            'npsn' => '87654321',
            'name' => 'SMK Negeri 2 Other',
            'email' => 'smkn2@test.sch.id',
            'address' => 'Jl. Lain No. 2',
            'phone' => '021-112233',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah',
            'email' => 'admin@smkn1test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
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

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa PKL Test',
            'email' => 'siswa@smkn1test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $major = Major::create(['school_id' => $this->school->id, 'name' => 'RPL', 'code' => 'RPL']);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'major_id' => $major->id, 'name' => 'XII RPL 1', 'grade_level' => 'XII', 'academic_year' => '2026/2027']);
        $this->student = Student::create(['user_id' => $this->studentUser->id, 'class_id' => $class->id, 'nisn' => '1234567890', 'nis' => '12345']);

        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Semester Gasal 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $this->company->id,
            'teacher_id' => $this->teacher->id,
            'pkl_period_id' => $period->id,
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);
    }

    public function test_student_can_submit_incident_and_notifications_are_dispatched(): void
    {
        $file = UploadedFile::fake()->image('bukti_k3.jpg');

        $response = $this->actingAs($this->studentUser)
            ->post(route('student.incident.store'), [
                'category' => 'safety_health',
                'urgency' => 'critical',
                'subject' => 'Pelindung mesin rusak dan membahayakan',
                'description' => 'Bagian pelindung mesin pemotong tidak terpasang dan hampir mencederai tangan siswa.',
                'attachment' => $file,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('incident_reports', [
            'student_pkl_id' => $this->spkl->id,
            'category' => 'safety_health',
            'urgency' => 'critical',
            'status' => 'submitted',
        ]);

        // Guru Pembimbing and Admin Sekolah should receive notification
        $this->assertEquals(1, $this->teacherUser->unreadNotifications()->count());
        $this->assertEquals(1, $this->adminUser->unreadNotifications()->count());

        // DU/DI should NOT receive any notification (Strict Privacy Rule)
        $this->assertEquals(0, $this->dudiUser->unreadNotifications()->count());
    }

    public function test_teacher_can_record_followup_and_resolve_incident(): void
    {
        $report = IncidentReport::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'category' => 'curriculum_mismatch',
            'urgency' => 'medium',
            'subject' => 'Penugasan hanya menyapu dan mencuci mobil',
            'description' => 'Siswa RPL tidak diberikan penugasan teknologi sesuai kurikulum.',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->teacherUser)
            ->post(route('guru.incidents.followup', $report), [
                'action_note' => 'Telah menghubungi pimpinan DU/DI dan menyepakati reposisi ke divisi IT Support.',
                'status' => 'resolved',
            ]);

        $response->assertRedirect();
        $this->assertEquals('resolved', $report->fresh()->status);
        $this->assertNotNull($report->fresh()->resolved_at);

        $this->assertDatabaseHas('incident_followups', [
            'incident_report_id' => $report->id,
            'user_id' => $this->teacherUser->id,
        ]);
    }

    public function test_impersonation_lifecycle(): void
    {
        // 1. Admin Sekolah starts impersonating Teacher
        $this->actingAs($this->adminUser)
            ->post(route('impersonate.start', $this->teacherUser))
            ->assertRedirect(route('guru.dashboard'));

        $this->assertEquals($this->teacherUser->id, auth()->id());
        $this->assertTrue(session()->has('impersonator_id'));
        $this->assertEquals($this->adminUser->id, session('impersonator_id'));

        // 2. Stop impersonation
        $this->post(route('impersonate.leave'))
            ->assertRedirect(route('adminsekolah.dashboard'));

        $this->assertEquals($this->adminUser->id, auth()->id());
        $this->assertFalse(session()->has('impersonator_id'));

        // 3. Admin cannot impersonate user from another school
        $otherUser = User::create([
            'school_id' => $this->otherSchool->id,
            'name' => 'Guru Other School',
            'email' => 'guru@smkn2other.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('impersonate.start', $otherUser))
            ->assertSessionHasErrors('impersonate');
    }
}
