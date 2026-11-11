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
use App\Services\GeolocationGuardService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GeolocationAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
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
            'gps_enabled' => true,
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

    public function test_geolocation_guard_detects_mock_and_blocks_weak_accuracy(): void
    {
        $service = new GeolocationGuardService();

        // 1. Accuracy guard
        $accHigh = $service->validateAccuracy(15);
        $this->assertFalse($accHigh['is_blocked']);
        $this->assertEquals('accurate', $accHigh['level']);

        $accWeak = $service->validateAccuracy(120);
        $this->assertTrue($accWeak['is_blocked']);
        $this->assertEquals('weak', $accWeak['level']);

        // 2. Mock GPS detection
        $this->assertTrue($service->detectMockLocation(['is_mocked' => 1]));
        $this->assertTrue($service->detectMockLocation(['accuracy' => 0.0, 'latitude' => -6.2]));
        $this->assertFalse($service->detectMockLocation(['accuracy' => 20.0, 'latitude' => -6.2]));
    }

    public function test_checkin_blocks_weak_gps_accuracy_over_threshold(): void
    {
        Carbon::setTestNow('2026-08-10 07:30:00');

        $response = $this->actingAs($this->studentUser)
            ->post(route('student.checkin'), [
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'accuracy' => 150, // Exceeds 80m threshold
            ]);

        $response->assertSessionHasErrors('accuracy');

        Carbon::setTestNow();
    }

    public function test_checkin_saves_accuracy_and_flags_mock_location(): void
    {
        Carbon::setTestNow('2026-08-10 07:30:00');

        $response = $this->actingAs($this->studentUser)
            ->post(route('student.checkin'), [
                'latitude' => -6.200000,
                'longitude' => 106.816666,
                'accuracy' => 25,
                'is_mocked' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'student_pkl_id' => $this->spkl->id,
            'accuracy_meters' => 25,
            'is_mock_detected' => 1,
        ]);

        Carbon::setTestNow();
    }

    public function test_journal_and_request_approval_dispatch_in_app_notifications(): void
    {
        // 1. Journal Approval Notification
        $journal = Journal::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-08-01',
            'activity_description' => 'Mengerjakan fitur A',
            'status' => 'submitted',
        ]);

        $this->actingAs($this->dudiUser)
            ->post(route('dudi.journals.process', $journal), [
                'action' => 'approve',
                'notes' => 'Kerja bagus',
            ]);

        // Student should have 1 unread notification
        $this->assertEquals(1, $this->studentUser->unreadNotifications()->count());
        $notif = $this->studentUser->notifications()->first();
        $this->assertEquals('journal_status', $notif->data['type']);

        // 2. Attendance Request Approval Notification
        $attReq = AttendanceRequest::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'type' => 'izin',
            'start_date' => '2026-08-05',
            'end_date' => '2026-08-05',
            'reason' => 'Ada acara',
            'status' => 'pending',
        ]);

        $this->actingAs($this->teacherUser)
            ->post(route('guru.requests.process', $attReq), [
                'action' => 'approve',
            ]);

        $this->assertEquals(2, $this->studentUser->unreadNotifications()->count());

        // 3. Mark All As Read endpoint
        $this->actingAs($this->studentUser)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertEquals(0, $this->studentUser->unreadNotifications()->count());
    }
}
