<?php

namespace Tests\Feature;

use App\Models\Major;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected User $studentUser;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '10101010',
            'name' => 'SMK Negeri 1 Otentikasi',
            'address' => 'Jl. Merdeka No. 1',
            'phone' => '021-1234567',
            'email' => 'smkn1@otentikasi.sch.id',
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

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Administrator Sekolah',
            'email' => 'admin@otentikasi.sch.id',
            'password' => bcrypt('AdminSecret123!'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Ahmad Siswa',
            'email' => 'ahmad@otentikasi.sch.id',
            'password' => bcrypt('SiswaSecret123!'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_id' => $class->id,
            'nis' => '2026001',
            'nisn' => '0081234567',
        ]);
    }

    public function test_login_page_renders_with_accessibility_attributes(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('VokaTrack');
        $response->assertSee('Masuk ke Sistem');
        $response->assertSee('autocapitalize="none"', false);
        $response->assertSee('autocomplete="username email"', false);
        $response->assertSee('id="pwd-toggle-btn"', false);
    }

    public function test_login_with_exact_credentials_for_different_roles(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@otentikasi.sch.id',
            'password' => 'AdminSecret123!',
        ]);

        $response->assertRedirect(route('adminsekolah.dashboard'));
        $this->assertAuthenticatedAs($this->adminUser);
    }

    public function test_login_normalizes_uppercase_and_whitespace(): void
    {
        // Test with uppercase and extra leading/trailing whitespace
        $response = $this->post('/login', [
            'email' => '  Admin@Otentikasi.Sch.Id  ',
            'password' => 'AdminSecret123!',
        ]);

        $response->assertRedirect(route('adminsekolah.dashboard'));
        $this->assertAuthenticatedAs($this->adminUser);
    }

    public function test_student_can_login_using_nisn(): void
    {
        $response = $this->post('/login', [
            'email' => '0081234567',
            'password' => 'SiswaSecret123!',
        ]);

        $response->assertRedirect(route('student.home'));
        $this->assertAuthenticatedAs($this->studentUser);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->adminUser->update(['is_active' => false]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@otentikasi.sch.id',
            'password' => 'AdminSecret123!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_school_blocks_login_for_tenant_users(): void
    {
        $this->school->update(['is_active' => false]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@otentikasi.sch.id',
            'password' => 'AdminSecret123!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_rate_limiter_blocks_after_5_consecutive_failed_attempts(): void
    {
        RateLimiter::clear(md5('admin@otentikasi.sch.id|127.0.0.1'));

        // 5 Failed login attempts
        for ($i = 1; $i <= 5; $i++) {
            $resp = $this->from('/login')->post('/login', [
                'email' => 'admin@otentikasi.sch.id',
                'password' => 'WrongPassword!',
            ]);
            $resp->assertRedirect('/login');
            $this->assertGuest();
        }

        // 6th Attempt: Should be rate-limited
        $resp6 = $this->from('/login')->post('/login', [
            'email' => 'admin@otentikasi.sch.id',
            'password' => 'WrongPassword!',
        ]);

        $resp6->assertRedirect('/login');
        $resp6->assertSessionHasErrors('email');

        $errorMessage = session('errors')->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan masuk', $errorMessage);
    }

    public function test_consecutive_successful_logins_do_not_get_throttled(): void
    {
        // 6 Successful logins (should not be blocked by IP throttle)
        for ($i = 1; $i <= 6; $i++) {
            $resp = $this->post('/login', [
                'email' => 'admin@otentikasi.sch.id',
                'password' => 'AdminSecret123!',
            ]);
            $resp->assertRedirect(route('adminsekolah.dashboard'));
            $this->assertAuthenticatedAs($this->adminUser);

            $this->post('/logout');
            $this->assertGuest();
        }
    }

    public function test_logout_post_invalidates_session_and_redirects(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
        $this->assertGuest();
    }

    public function test_logout_get_request_handled_safely_without_405(): void
    {
        $this->actingAs($this->studentUser);

        // GET request to /logout should not throw 405 Method Not Allowed
        $response = $this->get('/logout');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
        $this->assertGuest();
    }

    public function test_unrecognized_role_does_not_cause_infinite_redirect_loop(): void
    {
        // User with corrupted/invalid role
        $corruptUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Corrupt Role User',
            'email' => 'corrupt@otentikasi.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa', // start as valid enum
            'is_active' => true,
        ]);

        // Directly override attribute bypassing enum validation in memory
        $corruptUser->role = 'unknown-alien-role';

        $this->actingAs($corruptUser);

        // Accessing login while authenticated with invalid role should gracefully log out
        $response = $this->get('/login');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_intended_url_redirection(): void
    {
        session(['url.intended' => route('adminsekolah.students')]);

        $response = $this->post('/login', [
            'email' => 'admin@otentikasi.sch.id',
            'password' => 'AdminSecret123!',
        ]);

        $response->assertRedirect(route('adminsekolah.students'));
    }
}
