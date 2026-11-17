<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPasswordResetRequest;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\StudentPasswordResetRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordAndTeacherResetTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected User $studentUser;
    protected Student $student;
    protected StudentPkl $studentPkl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '77889900',
            'name' => 'SMK Negeri 1 Riset',
            'address' => 'Jl. Teknologi No. 1',
            'phone' => '021-998877',
            'email' => 'info@smkn1riset.sch.id',
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Budi Pembimbing',
            'email' => 'budi.guru@smkn1riset.sch.id',
            'wa_number' => '081234567890',
            'password' => Hash::make('password123'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'nip' => '198001012005011001',
        ]);

        $major = \App\Models\Major::create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII RPL 1',
            'academic_year' => '2026/2027',
        ]);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Ahmad Siswa',
            'email' => 'ahmad.siswa@smkn1riset.sch.id',
            'password' => Hash::make('passwordLama123'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_id' => $class->id,
            'nis' => '12345',
            'nisn' => '0055123456',
        ]);

        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Solusi Siber',
            'address' => 'Jl. Industri No. 5',
            'contact_person' => 'Bapak Hendra',
            'phone' => '081299998888',
        ]);

        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Semester Gasal 2026/2027',
            'start_date' => now()->subMonths(1),
            'end_date' => now()->addMonths(3),
            'is_active' => true,
        ]);

        $this->studentPkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
        ]);
    }

    public function test_login_page_renders_forgot_password_link(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee(route('password.request'));
        $response->assertSee('Lupa password?');
    }

    public function test_forgot_password_page_renders_properly(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertSee('Permohonan Reset Sandi');
        $response->assertSee('Alamat Email Akun Siswa');
    }

    public function test_anti_enumeration_returns_generic_success_message_for_unknown_email(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'tidak.ada@sekolah.sch.id',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('student_password_reset_requests', [
            'email' => 'tidak.ada@sekolah.sch.id',
        ]);
        Notification::assertNothingSent();
    }

    public function test_student_requesting_reset_creates_pending_request_and_notifies_teacher(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'ahmad.siswa@smkn1riset.sch.id',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('student_password_reset_requests', [
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'email' => 'ahmad.siswa@smkn1riset.sch.id',
            'status' => 'pending',
        ]);

        Notification::assertSentTo(
            $this->teacherUser,
            StudentPasswordResetRequestedNotification::class,
            function ($notification, $channels) {
                return $notification->student->id === $this->student->id
                    && in_array('database', $channels);
            }
        );
    }

    public function test_teacher_can_see_pending_reset_request_on_monitoring_page(): void
    {
        StudentPasswordResetRequest::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'email' => $this->studentUser->email,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->teacherUser)->get(route('guru.monitoring'));

        $response->assertOk();
        $response->assertSee('Permohonan Reset Kata Sandi Siswa Masuk');
        $response->assertSee($this->studentUser->name);
        $response->assertSee($this->student->nisn);
    }

    public function test_teacher_resetting_student_password_marks_request_as_completed(): void
    {
        $resetRequest = StudentPasswordResetRequest::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'email' => $this->studentUser->email,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->teacherUser)->post(
            route('guru.students.reset-password', $this->student),
            ['new_password' => 'SandiBaruSiswa2026!']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $resetRequest->refresh();
        $this->assertSame('completed', $resetRequest->status);

        $this->studentUser->refresh();
        $this->assertTrue(Hash::check('SandiBaruSiswa2026!', $this->studentUser->password));
        $this->assertTrue($this->studentUser->must_change_password);
    }

    public function test_teacher_can_see_pending_reset_request_on_dashboard(): void
    {
        StudentPasswordResetRequest::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'email' => $this->studentUser->email,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->teacherUser)->get(route('guru.dashboard'));

        $response->assertOk();
        $response->assertSee('Permohonan Reset Kata Sandi Siswa');
        $response->assertSee('Reset Kata Sandi Sekarang');
        $response->assertSee('Reset Sandi');
    }

    public function test_school_admin_can_reset_student_password(): void
    {
        $adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah PKL',
            'email' => 'admin.sekolah@smkn1riset.sch.id',
            'password' => Hash::make('AdminPass123!'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $resetRequest = StudentPasswordResetRequest::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'email' => $this->studentUser->email,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($adminUser)->post(
            route('adminsekolah.students.reset-password', $this->student),
            ['new_password' => 'AdminResetPassword123!']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $resetRequest->refresh();
        $this->assertSame('completed', $resetRequest->status);

        $this->studentUser->refresh();
        $this->assertTrue(Hash::check('AdminResetPassword123!', $this->studentUser->password));
        $this->assertTrue($this->studentUser->must_change_password);
    }
}
