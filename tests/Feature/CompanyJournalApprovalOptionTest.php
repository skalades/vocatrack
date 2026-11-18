<?php

namespace Tests\Feature;

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
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyJournalApprovalOptionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private Major $major;
    private SchoolClass $class;
    private PklPeriod $period;
    private Teacher $teacher;
    private User $guruUser;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '11223344',
            'name' => 'SMK Negeri 1 Test',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '022-123456',
            'email' => 'smkn1@test.sch.id',
            'is_active' => true,
        ]);

        $this->major = Major::create([
            'school_id' => $this->school->id,
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
        ]);

        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->major->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Periode PKL 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->guruUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing Test',
            'email' => 'guru@test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->teacher = Teacher::create(['user_id' => $this->guruUser->id, 'nip' => '198001012005011001']);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah Test',
            'email' => 'admin@test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
    }

    public function test_admin_sekolah_can_toggle_company_journal_verification_option(): void
    {
        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Industri Sibuk',
            'address' => 'Bandung',
            'contact_person' => 'Bapak Subur',
            'phone' => '08123456789',
            'work_days' => '5_days',
            'requires_industry_approval' => true,
        ]);

        $this->assertTrue($company->requires_industry_approval);

        // Update: Admin unchecks the option (industri sibuk / tidak berkenan verifikasi)
        $response = $this->actingAs($this->adminUser)->put(route('adminsekolah.companies.update', $company), [
            'name' => 'PT Industri Sibuk',
            'address' => 'Bandung',
            'contact_person' => 'Bapak Subur',
            'phone' => '08123456789',
            'work_days' => '5_days',
            // requires_industry_approval omitted (unchecked checkbox)
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $company->refresh();
        $this->assertFalse($company->requires_industry_approval);

        // Update: Admin checks the option back on
        $response = $this->actingAs($this->adminUser)->put(route('adminsekolah.companies.update', $company), [
            'name' => 'PT Industri Sibuk',
            'address' => 'Bandung',
            'contact_person' => 'Bapak Subur',
            'phone' => '08123456789',
            'work_days' => '5_days',
            'requires_industry_approval' => '1',
        ]);

        $response->assertRedirect();
        $company->refresh();
        $this->assertTrue($company->requires_industry_approval);
    }

    public function test_journal_routes_directly_to_teacher_when_industry_verification_is_disabled(): void
    {
        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Industri Mandiri',
            'address' => 'Jl. Industri No. 20',
            'contact_person' => 'Ibu Wati',
            'phone' => '082211223344',
            'work_days' => '5_days',
            'requires_industry_approval' => false, // Industri tidak memverifikasi
            'is_pkl_started' => true,
        ]);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Mandiri',
            'email' => 'siswa.mandiri@test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $this->class->id, 'nis' => '1001', 'nisn' => '0011223344']);

        $spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        // 1. Siswa submit jurnal harian
        $response = $this->actingAs($studentUser)->post(route('student.journal.store'), [
            'date' => Carbon::today()->toDateString(),
            'activity_description' => 'Melakukan pemeliharaan server database, konfigurasi backup harian, dan dokumentasi API internal.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $journal = Journal::where('student_pkl_id', $spkl->id)->first();
        $this->assertNotNull($journal);

        // Verifikasi status jurnal langsung review_guru (bukan submitted)
        $this->assertEquals('review_guru', $journal->status);

        // 2. Guru pembimbing dapat langsung menyetujui jurnal ke status approved
        $service = app(JournalService::class);
        $service->approve($journal, $this->guruUser, 'Aktivitas teknis bagus dan sesuai capaian pembelajaran.');

        $journal->refresh();
        $this->assertEquals('approved', $journal->status);
        $this->assertCount(1, $journal->approvals);
        $this->assertEquals('guru-pembimbing', $journal->approvals->first()->role_level);
    }

    public function test_journal_retains_two_tier_approval_when_industry_verification_is_enabled(): void
    {
        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Mitra Dua Tingkat',
            'address' => 'Jl. Mitra No. 10',
            'contact_person' => 'Bapak Handoko',
            'phone' => '083344556677',
            'work_days' => '5_days',
            'requires_industry_approval' => true, // Default: verifikasi 2 tingkat
            'is_pkl_started' => true,
        ]);

        $dudiUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Mentor Lapangan',
            'email' => 'mentor@mitra.com',
            'password' => bcrypt('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        $supervisor = Supervisor::create(['user_id' => $dudiUser->id, 'company_id' => $company->id]);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Reguler',
            'email' => 'siswa.reguler@test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $this->class->id, 'nis' => '1002', 'nisn' => '0011223345']);

        $spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'supervisor_id' => $supervisor->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        // Siswa submit jurnal
        $this->actingAs($studentUser)->post(route('student.journal.store'), [
            'date' => Carbon::today()->toDateString(),
            'activity_description' => 'Mengerjakan instalasi kabel jaringan LAN dan crimping RJ45 pada ruang server client.',
        ]);

        $journal = Journal::where('student_pkl_id', $spkl->id)->first();
        $this->assertEquals('submitted', $journal->status);

        $service = app(JournalService::class);

        // Guru tidak bisa menyetujui langsung bila requires_industry_approval = true
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->approve($journal, $this->guruUser, 'Catatan guru');
    }
}
