<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherSelfProfileTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $teacherUser;
    private Teacher $teacher;
    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '20206666',
            'name' => 'SMK Negeri 2 Garut',
            'email' => 'info@smkn2garut-profile.sch.id',
            'address' => 'Garut',
            'phone' => '0262',
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Awal',
            'email' => 'guru.profil@smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'nip' => '19800101',
        ]);

        Profile::create([
            'user_id' => $this->teacherUser->id,
            'phone' => '081111111111',
        ]);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Biasa',
            'email' => 'siswa.profil@smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
    }

    public function test_teacher_can_update_own_name_nip_and_phone_independently(): void
    {
        $this->actingAs($this->teacherUser)
            ->from(route('guru.dashboard'))
            ->post(route('guru.profile.update'), [
                'name' => 'Herdi Permana, S.Kom., M.Pd',
                'nip' => '198801012015011001',
                'phone' => '081234567890',
            ])
            ->assertRedirect(route('guru.dashboard'))
            ->assertSessionHas('success');

        $this->teacherUser->refresh();
        $this->teacher->refresh();

        $this->assertSame('Herdi Permana, S.Kom., M.Pd', $this->teacherUser->name);
        $this->assertSame('6281234567890', $this->teacherUser->wa_number);
        $this->assertSame('6281234567890', $this->teacherUser->profile->phone);
        $this->assertSame('198801012015011001', $this->teacher->nip);
    }

    public function test_teacher_profile_rejects_invalid_phone_number(): void
    {
        $this->actingAs($this->teacherUser)
            ->from(route('guru.dashboard'))
            ->post(route('guru.profile.update'), [
                'name' => 'Guru Awal',
                'nip' => '19800101',
                'phone' => '12345',
            ])
            ->assertRedirect(route('guru.dashboard'))
            ->assertSessionHasErrors('phone');
    }

    public function test_contact_preferences_also_syncs_profile_phone(): void
    {
        $this->actingAs($this->teacherUser)
            ->post(route('profile.contact-preferences'), [
                'wa_number' => '085211223344',
            ])
            ->assertSessionHas('success');

        $this->teacherUser->refresh();
        $this->assertSame('6285211223344', $this->teacherUser->wa_number);
        $this->assertSame('6285211223344', $this->teacherUser->profile->phone);
    }

    public function test_non_teacher_cannot_access_teacher_profile_endpoint(): void
    {
        $this->actingAs($this->studentUser)
            ->post(route('guru.profile.update'), [
                'name' => 'Hacker',
                'nip' => '999',
                'phone' => '081234567890',
            ])
            ->assertForbidden();
    }

    public function test_teacher_mobile_and_desktop_dashboards_render_profile_modal(): void
    {
        $mobile = $this->actingAs($this->teacherUser)
            ->get(route('guru.dashboard', ['view' => 'mobile']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="modal-teacher-profile"', $mobile);
        $this->assertStringContainsString('openTeacherProfileModal()', $mobile);

        $desktop = $this->actingAs($this->teacherUser)
            ->get(route('guru.dashboard', ['view' => 'desktop']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="modal-teacher-profile"', $desktop);
        $this->assertStringContainsString('openTeacherProfileModal()', $desktop);
    }
}
