<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $admin;
    protected User $guruUser;
    protected Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = $this->makeSchool('11112222', 'SMK Reset Satu');
        $this->admin = $this->makeUser($this->school, 'admin-sekolah', 'admin@reset1.sch.id');
        [$this->guruUser, $this->teacher] = $this->makeTeacher($this->school, 'guru@reset1.sch.id');
    }

    protected function makeSchool(string $npsn, string $name): School
    {
        return School::create([
            'npsn' => $npsn,
            'name' => $name,
            'address' => 'Jl. Uji Coba',
            'phone' => '021-111',
            'email' => "info{$npsn}@smk.sch.id",
            'is_active' => true,
        ]);
    }

    protected function makeUser(School $school, string $role, string $email): User
    {
        return User::create([
            'school_id' => $school->id,
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'password-lama-123',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    protected function makeTeacher(School $school, string $email): array
    {
        $user = $this->makeUser($school, 'guru-pembimbing', $email);
        $teacher = Teacher::create(['user_id' => $user->id, 'nip' => '1234567890']);

        return [$user, $teacher];
    }

    public function test_admin_can_reset_teacher_password_with_custom_value(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('adminsekolah.teachers.reset-password', $this->teacher),
            ['new_password' => 'SandiBaru2026']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('temporary_credential', fn ($c) => $c['email'] === 'guru@reset1.sch.id'
            && $c['password'] === 'SandiBaru2026');

        $this->guruUser->refresh();
        $this->assertTrue(Hash::check('SandiBaru2026', $this->guruUser->password));
        $this->assertFalse(Hash::check('password-lama-123', $this->guruUser->password));
        $this->assertTrue($this->guruUser->must_change_password);
    }

    public function test_blank_password_generates_random_temporary_password(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('adminsekolah.teachers.reset-password', $this->teacher),
            ['new_password' => '']
        );

        $response->assertSessionHas('temporary_credential');
        $generated = session('temporary_credential')['password'];

        $this->assertSame(12, strlen($generated));
        $this->assertTrue(Hash::check($generated, $this->guruUser->fresh()->password));
    }

    public function test_reset_reactivates_inactive_teacher_and_writes_audit_log(): void
    {
        $this->guruUser->update(['is_active' => false]);

        $this->actingAs($this->admin)->post(
            route('adminsekolah.teachers.reset-password', $this->teacher),
            ['new_password' => 'SandiBaru2026']
        );

        $this->assertTrue($this->guruUser->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $this->school->id,
            'user_id' => $this->admin->id,
            'action' => 'password_reset',
            'module' => 'security',
        ]);
    }

    public function test_password_shorter_than_eight_chars_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('adminsekolah.teachers.reset-password', $this->teacher),
            ['new_password' => 'pendek']
        );

        $response->assertSessionHasErrors('new_password');
        $this->assertTrue(Hash::check('password-lama-123', $this->guruUser->fresh()->password));
    }

    public function test_admin_cannot_reset_teacher_of_another_school(): void
    {
        $otherSchool = $this->makeSchool('33334444', 'SMK Reset Dua');
        [$otherUser, $otherTeacher] = $this->makeTeacher($otherSchool, 'guru@reset2.sch.id');

        $response = $this->actingAs($this->admin)->post(
            route('adminsekolah.teachers.reset-password', $otherTeacher),
            ['new_password' => 'SandiBaru2026']
        );

        $response->assertForbidden();
        $this->assertTrue(Hash::check('password-lama-123', $otherUser->fresh()->password));
    }

    public function test_non_admin_roles_cannot_reset_teacher_password(): void
    {
        $response = $this->actingAs($this->guruUser)->post(
            route('adminsekolah.teachers.reset-password', $this->teacher),
            ['new_password' => 'SandiBaru2026']
        );

        $this->assertContains($response->getStatusCode(), [302, 403]);
        $this->assertTrue(Hash::check('password-lama-123', $this->guruUser->fresh()->password));
    }
}
