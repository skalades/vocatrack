<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected User $studentUser;
    protected Student $student;
    protected SchoolClass $class;
    protected Company $company;
    protected StudentPkl $studentPkl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'SMK Telkom Malang',
            'npsn' => '20500999',
            'email' => 'smk@example.com',
            'address' => 'Jl. Danau Ranau No. 1 Malang',
            'phone' => '0341-712500',
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Drs. Hendro Wibowo',
            'email' => 'hendro.guru@example.com',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'nip' => '198002022005011003',
        ]);

        $major = Major::create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
        ]);

        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII RPL 1',
            'academic_year' => '2026/2027',
        ]);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Ananda Pratama',
            'email' => 'ananda@example.com',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_id' => $this->class->id,
            'nis' => '12345',
            'nisn' => '0012345678',
        ]);

        $this->company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Teknologi Maju Bangsa',
            'address' => 'Jl. Industri No. 10',
            'contact_person' => 'Bambang HRD',
            'phone' => '0341-998877',
            'latitude' => -7.9826,
            'longitude' => 112.6308,
            'radius_meters' => 100,
        ]);

        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Semester Gasal 2026/2027',
            'start_date' => Carbon::today()->subMonths(1)->toDateString(),
            'end_date' => Carbon::today()->addMonths(2)->toDateString(),
            'is_active' => true,
        ]);

        $this->studentPkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $this->company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
        ]);
    }

    public function test_student_model_auto_generates_parent_token(): void
    {
        $this->assertNotEmpty($this->student->parent_token);
        $this->assertTrue($this->student->is_parent_portal_active);
        $this->assertStringContainsString($this->student->parent_token, $this->student->parent_monitoring_url);
    }

    public function test_public_parent_portal_can_be_viewed_without_login(): void
    {
        $today = Carbon::today()->toDateString();

        // Create today attendance
        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->studentPkl->id,
            'date' => $today,
            'status' => 'hadir',
            'checkin_time' => '07:30:00',
            'is_within_radius' => true,
        ]);

        // Create a journal
        Journal::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->studentPkl->id,
            'date' => $today,
            'activity_description' => 'Mengerjakan modul autentikasi API dan pengujian unit.',
            'status' => 'approved',
        ]);

        $response = $this->get(route('parent.monitor', ['token' => $this->student->parent_token]));

        $response->assertStatus(200);
        $response->assertSee('Ananda Pratama');
        $response->assertSee('PT Teknologi Maju Bangsa');
        $response->assertSee('Hadir Tepat Waktu');
        $response->assertSee('Mengerjakan modul autentikasi API');
        $response->assertSee('Disetujui');
    }

    public function test_parent_portal_returns_404_when_token_is_invalid(): void
    {
        $response = $this->get(route('parent.monitor', ['token' => 'token-palsu-ngawur-123']));

        $response->assertStatus(404);
        $response->assertSee('Tautan Tidak Ditemukan');
    }

    public function test_parent_portal_returns_403_when_portal_is_disabled(): void
    {
        $this->student->update(['is_parent_portal_active' => false]);

        $response = $this->get(route('parent.monitor', ['token' => $this->student->parent_token]));

        $response->assertStatus(403);
        $response->assertSee('Portal Dinonaktifkan');
    }

    public function test_authenticated_student_can_regenerate_parent_token(): void
    {
        $oldToken = $this->student->parent_token;

        $response = $this->actingAs($this->studentUser)
            ->post(route('student.parent-token.regenerate'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->student->refresh();
        $newToken = $this->student->parent_token;

        $this->assertNotEquals($oldToken, $newToken);

        // Old token now gives 404
        $this->get(route('parent.monitor', ['token' => $oldToken]))
            ->assertStatus(404);

        // New token works
        $this->get(route('parent.monitor', ['token' => $newToken]))
            ->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_regenerate_parent_token(): void
    {
        $response = $this->post(route('student.parent-token.regenerate'));

        $response->assertRedirect(route('login'));
    }
}
