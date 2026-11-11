<?php

namespace Tests\Feature;

use App\Models\AttendanceRequest;
use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAndIdorTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $teacherUserA;
    protected Teacher $teacherA;
    protected User $teacherUserB;
    protected Teacher $teacherB;
    protected Company $companyA;
    protected User $superUserA;
    protected Supervisor $supervisorA;
    protected Company $companyB;
    protected User $superUserB;
    protected Supervisor $supervisorB;
    protected StudentPkl $spklA;
    protected AttendanceRequest $requestA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMK Negeri 1 Test',
            'address' => 'Jl. Pendidikan',
            'phone' => '021-123456',
            'email' => 'smkn1@test.sch.id',
            'is_active' => true,
        ]);

        $major = Major::create([
            'school_id' => $this->school->id,
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        // Teacher A
        $this->teacherUserA = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru A',
            'email' => 'guruA@test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->teacherA = Teacher::create([
            'user_id' => $this->teacherUserA->id,
            'nip' => '1111',
        ]);

        // Teacher B
        $this->teacherUserB = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru B',
            'email' => 'guruB@test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->teacherB = Teacher::create([
            'user_id' => $this->teacherUserB->id,
            'nip' => '2222',
        ]);

        // Company A & Supervisor A
        $this->companyA = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Teknologi A',
            'address' => 'Jakarta',
            'contact_person' => 'Mentor A',
            'phone' => '0811',
            'work_days' => '5_days',
        ]);
        $this->superUserA = User::create([
            'school_id' => $this->school->id,
            'name' => 'Supervisor A',
            'email' => 'superA@test.com',
            'password' => bcrypt('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        $this->supervisorA = Supervisor::create([
            'user_id' => $this->superUserA->id,
            'company_id' => $this->companyA->id,
            'position' => 'Senior Mentor',
        ]);

        // Company B & Supervisor B
        $this->companyB = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Teknologi B',
            'address' => 'Bandung',
            'contact_person' => 'Mentor B',
            'phone' => '0822',
            'work_days' => '5_days',
        ]);
        $this->superUserB = User::create([
            'school_id' => $this->school->id,
            'name' => 'Supervisor B',
            'email' => 'superB@test.com',
            'password' => bcrypt('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        $this->supervisorB = Supervisor::create([
            'user_id' => $this->superUserB->id,
            'company_id' => $this->companyB->id,
            'position' => 'Senior Mentor',
        ]);

        // Student A assigned to Teacher A and Company A
        $studentUserA = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa A',
            'email' => 'siswaA@test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $studentA = Student::create([
            'user_id' => $studentUserA->id,
            'class_id' => $class->id,
            'nis' => '1001',
            'nisn' => '0010010011',
        ]);

        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Semester Gasal 2026/2027',
            'start_date' => now()->subMonths(1)->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'is_active' => true,
        ]);

        $this->spklA = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $studentA->id,
            'company_id' => $this->companyA->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $this->teacherA->id,
            'supervisor_id' => $this->supervisorA->id,
            'status' => 'active',
        ]);

        $this->requestA = AttendanceRequest::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklA->id,
            'type' => 'izin',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason' => 'Keperluan keluarga penting',
            'status' => 'pending',
        ]);
    }

    public function test_teacher_cannot_process_request_of_another_teachers_student(): void
    {
        // Teacher B tries to approve Student A's attendance request (mentored by Teacher A)
        $response = $this->actingAs($this->teacherUserB)->post(
            route('guru.requests.process', $this->requestA->id),
            ['action' => 'approve']
        );

        $response->assertStatus(403);
    }

    public function test_teacher_cannot_score_another_teachers_student(): void
    {
        // Teacher B tries to grade Student A (mentored by Teacher A)
        $response = $this->actingAs($this->teacherUserB)->post(
            route('guru.assessments.store'),
            [
                'student_pkl_id' => $this->spklA->id,
                'score_teacher_report' => 95,
            ]
        );

        $response->assertStatus(403);
    }

    public function test_supervisor_cannot_process_request_of_another_companys_student(): void
    {
        // Change request type to dinas_luar (DU/DI domain)
        $this->requestA->update(['type' => 'dinas_luar']);

        // Supervisor B (Company B) tries to approve Student A (placed at Company A)
        $response = $this->actingAs($this->superUserB)->post(
            route('dudi.requests.process', $this->requestA->id),
            ['action' => 'approve']
        );

        $response->assertStatus(403);
    }

    public function test_supervisor_cannot_score_another_companys_student(): void
    {
        // Supervisor B (Company B) tries to grade Student A (Company A)
        $response = $this->actingAs($this->superUserB)->post(
            route('dudi.assessments.store'),
            [
                'student_pkl_id' => $this->spklA->id,
                'score_dudi_discipline' => 90,
                'score_dudi_technical' => 90,
                'score_dudi_initiative' => 90,
            ]
        );

        $response->assertStatus(403);
    }

    public function test_checkin_rejects_malicious_photo_mime_types(): void
    {
        $studentUser = $this->spklA->student->user;

        // Attempting to send a malicious php or exe base64 string
        $maliciousBase64 = 'data:image/php;base64,' . base64_encode('<?php echo "exploit";');

        $response = $this->actingAs($studentUser)->post(
            route('student.checkin'),
            [
                'latitude' => -6.2,
                'longitude' => 106.8,
                'photo' => $maliciousBase64,
            ]
        );

        $response->assertSessionHasErrors(['photo']);
    }

    public function test_fail_closed_tenant_isolation_when_school_id_is_null(): void
    {
        // A user with null school_id and non-super-admin role
        $orphanUser = User::create([
            'school_id' => null,
            'name' => 'User Tanpa Sekolah',
            'email' => 'orphan@test.com',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);

        $this->actingAs($orphanUser);

        // Due to fail-closed scope (whereRaw('1 = 0')), this query must return 0 records
        $companies = Company::all();
        $this->assertCount(0, $companies);
    }
}
