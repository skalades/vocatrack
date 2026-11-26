<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\Journal;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaliKelasWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected User $waliUser;
    protected Teacher $waliTeacher;
    protected User $otherTeacherUser;
    protected Teacher $otherTeacher;
    protected Major $majorRpl;
    protected SchoolClass $classRpl1;
    protected SchoolClass $classRpl2;
    protected PklPeriod $period;
    protected Company $company;
    protected Student $studentRpl1;
    protected Student $studentRpl2;
    protected StudentPkl $spklRpl1;
    protected StudentPkl $spklRpl2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '20202020',
            'name' => 'SMK Negeri 2 VokaTrack',
            'address' => 'Jl. Merdeka No. 1',
            'phone' => '021-987654',
            'email' => 'smkn2@vokatrack.sch.id',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah',
            'email' => 'admin@vokatrack.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $this->waliUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Ibu Rina Wali Kelas',
            'email' => 'rina.wali@vokatrack.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->waliTeacher = Teacher::create([
            'user_id' => $this->waliUser->id,
            'nip' => '198701012010012001',
        ]);

        $this->otherTeacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Pak Joko Pembimbing',
            'email' => 'joko.guru@vokatrack.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->otherTeacher = Teacher::create([
            'user_id' => $this->otherTeacherUser->id,
            'nip' => '198401012009011002',
        ]);

        $this->majorRpl = Major::create([
            'school_id' => $this->school->id,
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
        ]);

        $this->classRpl1 = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorRpl->id,
            'homeroom_teacher_id' => $this->waliTeacher->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $this->classRpl2 = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorRpl->id,
            'homeroom_teacher_id' => null,
            'name' => 'XII RPL 2',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Periode PKL 2026/2027',
            'academic_year' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Mitra Digital Nusantara',
            'address' => 'Jl. Teknologi No. 88',
            'contact_person' => 'Bapak Andi',
            'phone' => '081200001111',
            'work_days' => '5_days',
        ]);

        $studentUser1 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Kelas Satu',
            'email' => 'siswa1@vokatrack.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $this->studentRpl1 = Student::create([
            'user_id' => $studentUser1->id,
            'class_id' => $this->classRpl1->id,
            'nis' => '10001',
            'nisn' => '0010000001',
        ]);

        $studentUser2 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Kelas Dua',
            'email' => 'siswa2@vokatrack.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $this->studentRpl2 = Student::create([
            'user_id' => $studentUser2->id,
            'class_id' => $this->classRpl2->id,
            'nis' => '10002',
            'nisn' => '0010000002',
        ]);

        $this->spklRpl1 = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->studentRpl1->id,
            'company_id' => $this->company->id,
            'teacher_id' => $this->otherTeacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
        ]);

        $this->spklRpl2 = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->studentRpl2->id,
            'company_id' => $this->company->id,
            'teacher_id' => $this->otherTeacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
        ]);
    }

    public function test_homeroom_capability_detection()
    {
        $this->assertTrue($this->waliUser->isWaliKelas());
        $this->assertTrue($this->waliUser->isWaliKelas($this->classRpl1->id));
        $this->assertFalse($this->waliUser->isWaliKelas($this->classRpl2->id));
        $this->assertEquals([$this->classRpl1->id], $this->waliUser->homeroom_class_ids);

        $this->assertFalse($this->otherTeacherUser->isWaliKelas());
        $this->assertEmpty($this->otherTeacherUser->homeroom_class_ids);
    }

    public function test_admin_can_assign_and_update_homeroom_teacher_on_class()
    {
        $this->actingAs($this->adminUser);

        $storeResp = $this->post(route('adminsekolah.classes.store'), [
            'major_id' => $this->majorRpl->id,
            'homeroom_teacher_id' => $this->otherTeacher->id,
            'name' => 'XI RPL 1',
            'grade_level' => 'XI',
            'academic_year' => '2026/2027',
        ]);

        $storeResp->assertRedirect();
        $createdClass = SchoolClass::where('name', 'XI RPL 1')->first();
        $this->assertNotNull($createdClass);
        $this->assertEquals($this->otherTeacher->id, $createdClass->homeroom_teacher_id);
        $this->assertTrue($this->otherTeacherUser->fresh()->isWaliKelas());

        $updateResp = $this->put(route('adminsekolah.classes.update', $this->classRpl2), [
            'major_id' => $this->majorRpl->id,
            'homeroom_teacher_id' => $this->waliTeacher->id,
            'name' => 'XII RPL 2',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $updateResp->assertRedirect();
        $this->assertEquals($this->waliTeacher->id, $this->classRpl2->fresh()->homeroom_teacher_id);
    }

    public function test_non_walikelas_teacher_and_student_are_forbidden()
    {
        $this->actingAs($this->otherTeacherUser)
            ->get(route('walikelas.dashboard'))
            ->assertStatus(403);

        $this->actingAs($this->studentRpl1->user)
            ->get(route('walikelas.dashboard'))
            ->assertStatus(403);
    }

    public function test_login_redirect_for_pure_walikelas_and_dual_role_teacher()
    {
        // Guru yang hanya menjadi Wali Kelas (0 siswa bimbingan PKL) langsung ke dashboard Wali Kelas
        $resp = $this->post(route('login'), [
            'email' => $this->waliUser->email,
            'password' => 'password123',
        ]);
        $resp->assertRedirect(route('walikelas.dashboard'));

        $this->post(route('logout'));

        // Jika merangkap Guru Pembimbing PKL, login mengarah ke guru.dashboard dan tetap bisa buka /wali-kelas
        $this->spklRpl2->update(['teacher_id' => $this->waliTeacher->id]);

        $respDual = $this->post(route('login'), [
            'email' => $this->waliUser->email,
            'password' => 'password123',
        ]);
        $respDual->assertRedirect(route('guru.dashboard'));

        $this->actingAs($this->waliUser)
            ->get(route('walikelas.dashboard'))
            ->assertOk();
    }

    public function test_walikelas_pages_only_show_own_class_students_and_data()
    {
        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklRpl1->id,
            'date' => now()->toDateString(),
            'checkin_time' => '07:55:00',
            'status' => 'hadir',
        ]);
        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklRpl2->id,
            'date' => now()->toDateString(),
            'checkin_time' => '08:00:00',
            'status' => 'hadir',
        ]);

        Journal::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklRpl1->id,
            'date' => now()->toDateString(),
            'activity_description' => 'Membuat modul autentikasi Laravel',
            'status' => 'review_guru',
        ]);
        Journal::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklRpl2->id,
            'date' => now()->toDateString(),
            'activity_description' => 'Jurnal rahasia kelas lain',
            'status' => 'review_guru',
        ]);

        IncidentReport::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklRpl1->id,
            'category' => 'curriculum_mismatch',
            'urgency' => 'medium',
            'subject' => 'Kendala kelas satu',
            'description' => 'Detail kendala kelas satu',
            'status' => 'submitted',
        ]);
        IncidentReport::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklRpl2->id,
            'category' => 'safety_health',
            'urgency' => 'high',
            'subject' => 'Kendala kelas dua',
            'description' => 'Detail kendala kelas dua',
            'status' => 'submitted',
        ]);

        Assessment::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklRpl1->id,
            'score_dudi_discipline' => 90,
            'score_dudi_technical' => 88,
            'score_dudi_initiative' => 86,
            'score_teacher_report' => 85,
            'final_score' => 87.0,
        ]);

        $this->actingAs($this->waliUser);

        $this->get(route('walikelas.dashboard'))
            ->assertOk()
            ->assertSee('XII RPL 1')
            ->assertSee('Kendala kelas satu')
            ->assertDontSee('Kendala kelas dua');

        $this->get(route('walikelas.students'))
            ->assertOk()
            ->assertSee('Siswa Kelas Satu')
            ->assertDontSee('Siswa Kelas Dua');

        $this->get(route('walikelas.attendance'))
            ->assertOk()
            ->assertSee('Siswa Kelas Satu')
            ->assertDontSee('Siswa Kelas Dua');

        $this->get(route('walikelas.journals'))
            ->assertOk()
            ->assertSee('Membuat modul autentikasi Laravel')
            ->assertDontSee('Jurnal rahasia kelas lain');

        $this->get(route('walikelas.incidents'))
            ->assertOk()
            ->assertSee('Kendala kelas satu')
            ->assertDontSee('Kendala kelas dua');

        $this->get(route('walikelas.grades'))
            ->assertOk()
            ->assertSee('Siswa Kelas Satu')
            ->assertDontSee('Siswa Kelas Dua');

        $csv = $this->get(route('walikelas.grades', ['export' => 'csv']));
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('content-type'));
        $content = $csv->streamedContent();
        $this->assertStringContainsString('Siswa Kelas Satu', $content);
        $this->assertStringNotContainsString('Siswa Kelas Dua', $content);
    }

    public function test_walikelas_idor_protections_across_classes()
    {
        $this->actingAs($this->waliUser);

        // Tidak boleh filter ke class_id kelas lain
        $this->get(route('walikelas.dashboard', ['class_id' => $this->classRpl2->id]))
            ->assertStatus(403);

        // Tidak boleh reset password siswa kelas lain
        $this->post(route('walikelas.students.reset-password', $this->studentRpl2), [
            'new_password' => 'SecretPass123',
        ])->assertStatus(403);

        // Boleh reset password siswa kelasnya sendiri
        $this->post(route('walikelas.students.reset-password', $this->studentRpl1), [
            'new_password' => 'SecretPass123',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue($this->studentRpl1->user->fresh()->must_change_password);
    }

    public function test_walikelas_parent_links_are_scoped_to_homeroom_class()
    {
        $this->actingAs($this->waliUser);

        $this->get(route('walikelas.parent-links'))
            ->assertOk()
            ->assertSee('Siswa Kelas Satu')
            ->assertDontSee('Siswa Kelas Dua');

        // Anti-IDOR pada toggle dan regenerate siswa kelas lain
        $this->post(route('walikelas.parent-links.toggle', $this->studentRpl2))
            ->assertStatus(403);

        $this->post(route('walikelas.parent-links.regenerate', $this->studentRpl2))
            ->assertStatus(403);

        // Toggle & regenerate siswa kelas sendiri berhasil
        $this->post(route('walikelas.parent-links.toggle', $this->studentRpl1))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertFalse($this->studentRpl1->fresh()->is_parent_portal_active);
    }

    public function test_class_promotion_handles_homeroom_teacher_policy()
    {
        $xiClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorRpl->id,
            'homeroom_teacher_id' => $this->waliTeacher->id,
            'name' => 'XI RPL 9',
            'grade_level' => 'XI',
            'academic_year' => '2026/2027',
        ]);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Naik Kelas',
            'email' => 'naik@vokatrack.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $xiClass->id,
            'nis' => '10099',
            'nisn' => '0010000099',
        ]);

        $this->actingAs($this->adminUser);

        // Default: preserve_homeroom_teacher false -> wali kelas baru null
        $this->post(route('adminsekolah.classes.promote'), [
            'current_academic_year' => '2026/2027',
            'target_academic_year' => '2027/2028',
            'class_ids' => [$xiClass->id],
        ])->assertRedirect();

        $newClass = SchoolClass::where('name', 'XII RPL 9')->where('academic_year', '2027/2028')->first();
        $this->assertNotNull($newClass);
        $this->assertNull($newClass->homeroom_teacher_id);

        // Dengan preserve_homeroom_teacher = 1 pada kelas lain
        $xClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorRpl->id,
            'homeroom_teacher_id' => $this->otherTeacher->id,
            'name' => 'X RPL 9',
            'grade_level' => 'X',
            'academic_year' => '2026/2027',
        ]);
        $studentUser2 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Naik Kelas Dua',
            'email' => 'naik2@vokatrack.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Student::create([
            'user_id' => $studentUser2->id,
            'class_id' => $xClass->id,
            'nis' => '10100',
            'nisn' => '0010000100',
        ]);

        $this->post(route('adminsekolah.classes.promote'), [
            'current_academic_year' => '2026/2027',
            'target_academic_year' => '2027/2028',
            'class_ids' => [$xClass->id],
            'preserve_homeroom_teacher' => 1,
        ])->assertRedirect();

        $newXiClass = SchoolClass::where('name', 'XI RPL 9')->where('academic_year', '2027/2028')->first();
        $this->assertNotNull($newXiClass);
        $this->assertEquals($this->otherTeacher->id, $newXiClass->homeroom_teacher_id);
    }
}
