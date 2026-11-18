<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KepalaJurusanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected User $kajurUser;
    protected Teacher $kajurTeacher;
    protected User $regularTeacherUser;
    protected Teacher $regularTeacher;
    protected Major $majorRpl;
    protected Major $majorTkj;
    protected SchoolClass $classRpl;
    protected SchoolClass $classTkj;
    protected PklPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMK Negeri 1 Teknologi',
            'address' => 'Jl. Pendidikan No. 10',
            'phone' => '021-123456',
            'email' => 'smkn1@teknologi.sch.id',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah',
            'email' => 'admin@teknologi.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        // Guru 1 (Kajur RPL)
        $this->kajurUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Drs. Budi Santoso (Kajur)',
            'email' => 'budi.kajur@teknologi.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->kajurTeacher = Teacher::create([
            'user_id' => $this->kajurUser->id,
            'nip' => '198001012005011001',
        ]);

        // Guru 2 (Guru Biasa)
        $this->regularTeacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siti Aminah, S.Kom',
            'email' => 'siti.guru@teknologi.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->regularTeacher = Teacher::create([
            'user_id' => $this->regularTeacherUser->id,
            'nip' => '198505052010012002',
        ]);

        // Jurusan RPL dengan Kepala Jurusan $this->kajurTeacher
        $this->majorRpl = Major::create([
            'school_id' => $this->school->id,
            'head_teacher_id' => $this->kajurTeacher->id,
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
        ]);

        // Jurusan TKJ tanpa Kepala Jurusan
        $this->majorTkj = Major::create([
            'school_id' => $this->school->id,
            'head_teacher_id' => null,
            'code' => 'TKJ',
            'name' => 'Teknik Komputer dan Jaringan',
        ]);

        $this->classRpl = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorRpl->id,
            'name' => 'XII RPL 1',
            'academic_year' => '2026/2027',
            'grade_level' => 'XII',
        ]);

        $this->classTkj = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorTkj->id,
            'name' => 'XII TKJ 1',
            'academic_year' => '2026/2027',
            'grade_level' => 'XII',
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Periode PKL 2026/2027',
            'academic_year' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);
    }

    public function test_teacher_role_and_capability_detection()
    {
        $this->assertTrue($this->kajurUser->isKajur());
        $this->assertEquals($this->majorRpl->id, $this->kajurUser->headedMajor()->id);

        $this->assertFalse($this->regularTeacherUser->isKajur());
        $this->assertNull($this->regularTeacherUser->headedMajor());
    }

    public function test_admin_can_assign_and_update_head_teacher_to_major()
    {
        $this->actingAs($this->adminUser);

        // Update major TKJ to have regularTeacher as head
        $response = $this->put(route('adminsekolah.majors.update', $this->majorTkj), [
            'code' => 'TKJ',
            'name' => 'Teknik Komputer & Jaringan Updated',
            'head_teacher_id' => $this->regularTeacher->id,
        ]);

        $response->assertRedirect();
        $this->majorTkj->refresh();
        $this->assertEquals($this->regularTeacher->id, $this->majorTkj->head_teacher_id);
        $this->assertTrue($this->regularTeacherUser->fresh()->isKajur());
    }

    public function test_non_kajur_teacher_is_forbidden_from_accessing_kajur_routes()
    {
        $this->actingAs($this->regularTeacherUser);

        $response = $this->get(route('kajur.dashboard'));
        $response->assertStatus(403);

        $response2 = $this->get(route('kajur.companies'));
        $response2->assertStatus(403);
    }

    public function test_kajur_can_access_dashboard_and_see_major_data()
    {
        $this->actingAs($this->kajurUser);

        $response = $this->get(route('kajur.dashboard'));
        $response->assertOk();
        $response->assertSee('Rekayasa Perangkat Lunak');
        $response->assertSee('RPL');
    }

    public function test_kajur_can_add_company_and_automatically_binds_to_major()
    {
        $this->actingAs($this->kajurUser);

        $response = $this->post(route('kajur.companies.store'), [
            'name' => 'PT Solusi Teknologi Indonesia',
            'address' => 'Gedung Cyber 2 Lantai 5, Jakarta Selatan',
            'contact_person' => 'Iwan Darmawan',
            'phone' => '081299887766',
            'work_days' => '5_days',
            'gps_enabled' => 1,
            'latitude' => -6.2297,
            'longitude' => 106.8295,
            'radius_meters' => 150,
            // Opsional pembimbing dudi
            'supervisor_name' => 'Rian Hidayat',
            'supervisor_email' => 'rian.hidayat@solusitek.id',
            'supervisor_phone' => '081344556677',
            'supervisor_position' => 'Lead Software Engineer',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $company = Company::where('name', 'PT Solusi Teknologi Indonesia')->first();
        $this->assertNotNull($company);
        $this->assertEquals($this->school->id, $company->school_id);
        $this->assertEquals($this->kajurUser->id, $company->created_by_user_id);
        $this->assertEquals(150, $company->radius_meters);

        // Verifikasi otomatis terikat ke jurusan RPL
        $this->assertTrue($company->majors()->where('majors.id', $this->majorRpl->id)->exists());

        // Verifikasi akun supervisor dibuat
        $supervisorUser = User::where('email', 'rian.hidayat@solusitek.id')->first();
        $this->assertNotNull($supervisorUser);
        $this->assertEquals('pembimbing-dudi', $supervisorUser->role);
        $this->assertEquals($company->id, $supervisorUser->supervisor->company_id);
    }

    public function test_kajur_can_update_their_major_company()
    {
        $this->actingAs($this->kajurUser);

        $company = Company::create([
            'school_id' => $this->school->id,
            'created_by_user_id' => $this->kajurUser->id,
            'name' => 'PT Mitra Lama',
            'address' => 'Jl. Lama No. 1',
            'contact_person' => 'Pak Budi',
            'phone' => '0811111111',
            'work_days' => '5_days',
        ]);
        $company->majors()->attach($this->majorRpl->id);

        $response = $this->put(route('kajur.companies.update', $company), [
            'name' => 'PT Mitra Baru Berkembang',
            'address' => 'Jl. Baru No. 99',
            'contact_person' => 'Pak Budi Santoso',
            'phone' => '0822222222',
            'work_days' => '6_days',
        ]);

        $response->assertRedirect();
        $company->refresh();
        $this->assertEquals('PT Mitra Baru Berkembang', $company->name);
        $this->assertEquals('6_days', $company->work_days);
    }

    public function test_kajur_cannot_update_company_of_another_major_or_school()
    {
        $this->actingAs($this->kajurUser);

        // Company milik jurusan TKJ saja (tidak terhubung ke RPL)
        $tkjCompany = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Jaringan Khusus TKJ',
            'address' => 'Jl. Fiber No. 2',
            'contact_person' => 'Pak Jaringan',
            'phone' => '0833333333',
            'work_days' => '5_days',
        ]);
        $tkjCompany->majors()->attach($this->majorTkj->id);

        $response = $this->put(route('kajur.companies.update', $tkjCompany), [
            'name' => 'Hacked Company Name',
            'address' => 'Jl. Fake',
            'contact_person' => 'Hacker',
            'phone' => '089999999',
            'work_days' => '5_days',
        ]);

        $response->assertStatus(403);
    }

    public function test_kajur_can_assign_plotting_to_student_in_their_major()
    {
        $this->actingAs($this->kajurUser);

        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Software Cemerlang',
            'address' => 'Jl. Merak No. 5',
            'contact_person' => 'Andi',
            'phone' => '085555555',
        ]);
        $company->majors()->attach($this->majorRpl->id);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Bima Perkasa (Siswa RPL)',
            'email' => 'bima@siswa.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'siswa',
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $this->classRpl->id,
            'nis' => '10001',
            'nisn' => '0099887766',
        ]);

        // Kajur menugaskan dirinya sendiri ($this->kajurTeacher) sebagai guru pembimbing
        $response = $this->post(route('kajur.students.assign', $student), [
            'company_id' => $company->id,
            'teacher_id' => $this->kajurTeacher->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $spkl = StudentPkl::where('student_id', $student->id)->first();
        $this->assertNotNull($spkl);
        $this->assertEquals($company->id, $spkl->company_id);
        $this->assertEquals($this->kajurTeacher->id, $spkl->teacher_id);
        $this->assertEquals('active', $spkl->status);
    }

    public function test_kajur_cannot_assign_plotting_to_student_of_another_major()
    {
        $this->actingAs($this->kajurUser);

        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Software Cemerlang',
            'address' => 'Jl. Merak No. 5',
            'contact_person' => 'Andi',
            'phone' => '085555555',
        ]);
        $company->majors()->attach($this->majorRpl->id);

        $tkjStudentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Doni (Siswa TKJ)',
            'email' => 'doni@siswa.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'siswa',
        ]);
        $tkjStudent = Student::create([
            'user_id' => $tkjStudentUser->id,
            'class_id' => $this->classTkj->id,
            'nis' => '10002',
            'nisn' => '0011223344',
        ]);

        $response = $this->post(route('kajur.students.assign', $tkjStudent), [
            'company_id' => $company->id,
            'teacher_id' => $this->kajurTeacher->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_dual_role_kajur_can_seamlessly_access_both_portals()
    {
        $this->actingAs($this->kajurUser);

        // 1. Akses portal Kajur
        $kajurRes = $this->get(route('kajur.dashboard'));
        $kajurRes->assertOk();
        $kajurRes->assertSee('Manajemen Jurusan (RPL)');

        // 2. Akses portal Guru Pembimbing
        $guruRes = $this->get(route('guru.dashboard'));
        $guruRes->assertOk();
        $guruRes->assertSee('Panel Kajur (RPL)');
    }

    public function test_kajur_can_export_grades_csv()
    {
        $this->actingAs($this->kajurUser);

        $response = $this->get(route('kajur.grades', ['export' => 'csv']));
        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_kajur_can_view_attendance_monitoring_with_search()
    {
        $this->actingAs($this->kajurUser);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Carrel Munjid',
            'email' => 'carrel@siswa.sch.id',
            'password' => bcrypt('password123'),
            'role' => 'siswa',
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $this->classRpl->id,
            'nis' => '10099',
            'nisn' => '0083191727',
        ]);

        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Pertamina Geothermal',
            'address' => 'Area Geothermal',
            'contact_person' => 'Staff',
            'phone' => '0812345678',
        ]);
        $company->majors()->attach($this->majorRpl->id);

        $spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'pkl_period_id' => $this->period->id,
            'company_id' => $company->id,
            'teacher_id' => $this->kajurTeacher->id,
            'status' => 'active',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(2),
        ]);

        \App\Models\Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $spkl->id,
            'date' => now()->toDateString(),
            'checkin_time' => '12:50:00',
            'checkin_lat' => -7.12345,
            'checkin_lng' => 110.12345,
            'is_within_radius' => true,
            'accuracy_meters' => 15,
            'status' => 'hadir',
        ]);

        $response = $this->get(route('kajur.attendance', ['search' => 'Carrel']));
        $response->assertOk();
        $response->assertSee('Carrel Munjid');
        $response->assertSee('0083191727');
        $response->assertSee('PT Pertamina Geothermal');
        $response->assertSee('12:50');
        $response->assertSee('Dalam Radius');
    }

    public function test_kajur_can_reset_student_password_in_their_major()
    {
        $this->actingAs($this->kajurUser);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Muhammad Adzka',
            'email' => 'adzka@siswa.sch.id',
            'password' => bcrypt('oldpassword123'),
            'role' => 'siswa',
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $this->classRpl->id,
            'nis' => '10098',
            'nisn' => '0095803728',
        ]);

        $response = $this->post(route('kajur.students.reset-password', $student));
        $response->assertRedirect();
        $response->assertSessionHas('temporary_credential');

        $studentUser->refresh();
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('oldpassword123', $studentUser->password));
    }

    public function test_kajur_cannot_reset_student_password_of_another_major()
    {
        $this->actingAs($this->kajurUser);

        $tkjStudentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Lain',
            'email' => 'lain@siswa.sch.id',
            'password' => bcrypt('oldpassword123'),
            'role' => 'siswa',
        ]);
        $tkjStudent = Student::create([
            'user_id' => $tkjStudentUser->id,
            'class_id' => $this->classTkj->id,
            'nis' => '10097',
            'nisn' => '0095803799',
        ]);

        $response = $this->post(route('kajur.students.reset-password', $tkjStudent));
        $response->assertStatus(403);
    }
}
