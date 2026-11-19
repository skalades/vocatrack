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
use App\Services\WhatsAppGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlottingFilterAndParentLinkTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $admin;
    protected Major $majorGeologi;
    protected Major $majorRpl;
    protected Company $companyGeologi;
    protected Company $companyRpl;
    protected Company $companyGeneral;
    protected SchoolClass $classGeologi;
    protected SchoolClass $classRpl;
    protected Teacher $teacher;
    protected PklPeriod $period;

    protected Student $studentGeo;
    protected Student $studentRpl;
    protected StudentPkl $spklGeo;
    protected StudentPkl $spklRpl;

    protected function setUp(): void
    {
        parent::setUp();

        WhatsAppGatewayService::fake();

        $this->school = School::create([
            'name' => 'SMKN 1 Garut',
            'npsn' => '12345678',
            'email' => 'smkn1garut@test.com',
            'address' => 'Jl. Pahlawan Garut',
            'phone' => '0262123456',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah',
            'email' => 'admin@smkn1garut.test',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing A',
            'email' => 'guru@smkn1garut.test',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '198001012005011001',
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'PKL Angkatan 2026/2027',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'is_active' => true,
        ]);

        // Majors
        $this->majorGeologi = Major::create([
            'school_id' => $this->school->id,
            'name' => 'Geologi Pertambangan',
            'code' => 'GP',
        ]);

        $this->majorRpl = Major::create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
        ]);

        // Classes
        $this->classGeologi = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorGeologi->id,
            'name' => 'XII Geologi 1',
            'academic_year' => '2026/2027',
        ]);

        $this->classRpl = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorRpl->id,
            'name' => 'XII RPL 1',
            'academic_year' => '2026/2027',
        ]);

        // Companies: One for Geologi (is_pkl_started = true), One for RPL (is_pkl_started = false)
        $this->companyGeologi = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Geoservices Pertambangan',
            'address' => 'Bandung',
            'contact_person' => 'PIC Geologi',
            'phone' => '0811111111',
            'is_pkl_started' => true,
        ]);
        $this->companyGeologi->majors()->attach($this->majorGeologi->id);

        $this->companyRpl = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Tech Kreasi Digital',
            'address' => 'Jakarta',
            'contact_person' => 'PIC RPL',
            'phone' => '0822222222',
            'is_pkl_started' => false,
        ]);
        $this->companyRpl->majors()->attach($this->majorRpl->id);

        // General company open to all majors
        $this->companyGeneral = Company::create([
            'school_id' => $this->school->id,
            'name' => 'CV Multi Karya Mandiri',
            'address' => 'Garut',
            'contact_person' => 'PIC General',
            'phone' => '0833333333',
            'is_pkl_started' => true,
        ]);

        // Students
        $geoUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Budi Santoso Geologi',
            'email' => 'budi.geologi@test.com',
            'wa_number' => '081234567890',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Profile::create(['user_id' => $geoUser->id, 'phone' => '081234567890']);
        $this->studentGeo = Student::create([
            'user_id' => $geoUser->id,
            'class_id' => $this->classGeologi->id,
            'nis' => '1001',
            'nisn' => '2001001',
            'parent_token' => 'token-geologi-12345',
            'is_parent_portal_active' => true,
        ]);

        $rplUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siti Aisyah RPL',
            'email' => 'siti.rpl@test.com',
            'wa_number' => '089876543210',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Profile::create(['user_id' => $rplUser->id, 'phone' => '089876543210']);
        $this->studentRpl = Student::create([
            'user_id' => $rplUser->id,
            'class_id' => $this->classRpl->id,
            'nis' => '1002',
            'nisn' => '2001002',
            'parent_token' => 'token-rpl-67890',
            'is_parent_portal_active' => true,
        ]);

        // Plotting
        $this->spklGeo = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->studentGeo->id,
            'company_id' => $this->companyGeologi->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
        ]);

        $this->spklRpl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->studentRpl->id,
            'company_id' => $this->companyRpl->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        WhatsAppGatewayService::resetFake();
        parent::tearDown();
    }

    public function test_plotting_page_renders_with_company_filter_and_parent_link_action(): void
    {
        $response = $this->actingAs($this->admin)->get(route('adminsekolah.plotting'));

        $response->assertOk();
        $response->assertSee('Semua Perusahaan DU/DI');
        $response->assertSee($this->companyGeologi->name);
        $response->assertSee($this->companyRpl->name);
        $response->assertSee('Kirim Link Ortu');
    }

    public function test_filter_by_company_returns_only_matching_student_pkl(): void
    {
        // Filter by companyGeologi
        $response = $this->actingAs($this->admin)->get(route('adminsekolah.plotting', [
            'company_id' => $this->companyGeologi->id,
        ]));

        $response->assertOk();
        $studentPkls = $response->viewData('studentPkls');
        $this->assertCount(1, $studentPkls);
        $this->assertEquals($this->spklGeo->id, $studentPkls->first()->id);
    }

    public function test_filter_by_major_returns_matching_major_students(): void
    {
        // Filter by Geologi major
        $response = $this->actingAs($this->admin)->get(route('adminsekolah.plotting', [
            'major_id' => $this->majorGeologi->id,
        ]));

        $response->assertOk();
        $studentPkls = $response->viewData('studentPkls');
        $this->assertCount(1, $studentPkls);
        $this->assertEquals($this->spklGeo->id, $studentPkls->first()->id);
    }

    public function test_student_pkl_status_reflects_company_is_pkl_started_setting(): void
    {
        // Geologi company has is_pkl_started = true -> Status: AKTIF
        $this->assertTrue($this->spklGeo->company->is_pkl_started);
        $this->assertEquals('active', $this->spklGeo->computed_status);
        $this->assertEquals('AKTIF', $this->spklGeo->computed_status_label);

        // RPL company has is_pkl_started = false -> Status: BELUM MULAI (pending_start)
        $this->assertFalse($this->spklRpl->company->is_pkl_started);
        $this->assertEquals('pending_start', $this->spklRpl->computed_status);
        $this->assertEquals('BELUM MULAI', $this->spklRpl->computed_status_label);

        // Check HTML output
        $response = $this->actingAs($this->admin)->get(route('adminsekolah.plotting'));
        $response->assertOk();
        $response->assertSee('AKTIF');
        $response->assertSee('BELUM MULAI');
    }

    public function test_filter_by_status_active_and_pending_start(): void
    {
        // Filter by status = active (where company is_pkl_started = true)
        $responseActive = $this->actingAs($this->admin)->get(route('adminsekolah.plotting', [
            'status' => 'active',
        ]));
        $responseActive->assertOk();
        $activePkls = $responseActive->viewData('studentPkls');
        $this->assertCount(1, $activePkls);
        $this->assertEquals($this->spklGeo->id, $activePkls->first()->id);

        // Filter by status = pending_start (where company is_pkl_started = false)
        $responsePending = $this->actingAs($this->admin)->get(route('adminsekolah.plotting', [
            'status' => 'pending_start',
        ]));
        $responsePending->assertOk();
        $pendingPkls = $responsePending->viewData('studentPkls');
        $this->assertCount(1, $pendingPkls);
        $this->assertEquals($this->spklRpl->id, $pendingPkls->first()->id);
    }

    public function test_send_parent_link_automatically_to_student_phone(): void
    {
        $response = $this->actingAs($this->admin)->post(route('adminsekolah.plotting.send-parent-link', $this->spklGeo));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify WhatsApp message was dispatched via fake gateway
        $sentMessages = WhatsAppGatewayService::getSentMessages();
        $this->assertCount(1, $sentMessages);

        $sent = $sentMessages[0];
        $this->assertEquals('6281234567890', $sent['target']);
        $this->assertStringContainsString('token-geologi-12345', $sent['message']);
        $this->assertStringContainsString('PT Geoservices Pertambangan', $sent['message']);
        $this->assertStringContainsString('Budi Santoso Geologi', $sent['message']);
    }

    public function test_send_parent_link_fails_when_phone_is_missing(): void
    {
        // Remove phone number
        $this->studentGeo->user->update(['wa_number' => null]);
        $this->studentGeo->user->profile->update(['phone' => null]);

        $response = $this->actingAs($this->admin)->post(route('adminsekolah.plotting.send-parent-link', $this->spklGeo));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $sentMessages = WhatsAppGatewayService::getSentMessages();
        $this->assertCount(0, $sentMessages);
    }

    public function test_send_parent_link_forbidden_across_tenants(): void
    {
        // Other school
        $otherSchool = School::create([
            'name' => 'SMKN 2 Bandung',
            'npsn' => '87654321',
            'email' => 'smkn2@test.com',
            'address' => 'Bandung',
            'phone' => '022123456',
            'is_active' => true,
        ]);

        $otherAdmin = User::create([
            'school_id' => $otherSchool->id,
            'name' => 'Admin Sekolah Lain',
            'email' => 'admin@other.test',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $response = $this->actingAs($otherAdmin)->post(route('adminsekolah.plotting.send-parent-link', $this->spklGeo));

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
