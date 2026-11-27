<?php

namespace Tests\Feature;

use App\Models\Attendance;
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
use App\Services\AssessmentService;
use App\Services\CertificateService;
use App\Services\ParentMonitoringService;
use App\Services\PklMapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MultiCompanyPerPeriodPklTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected Student $student;
    protected Teacher $teacher;
    protected Company $companyA;
    protected Company $companyB;
    protected PklPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '20199900',
            'name' => 'SMK Negeri Multi Perusahaan',
            'address' => 'Jl. Industri No. 10',
            'phone' => '021-88990011',
            'email' => 'smkmulti@test.sch.id',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah',
            'email' => 'admin@multitest.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
        Profile::create(['user_id' => $this->adminUser->id, 'gender' => 'L']);

        $major = Major::create([
            'school_id' => $this->school->id,
            'code' => 'TKJ',
            'name' => 'Teknik Komputer dan Jaringan',
            'program_duration_years' => 3,
            'default_pkl_duration_months' => 6,
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII TKJ 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Budi Santoso',
            'email' => 'budi@multitest.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Profile::create(['user_id' => $studentUser->id, 'gender' => 'L']);
        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'nis' => '1001',
            'nisn' => '0011223344',
        ]);

        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Pak Guru',
            'email' => 'guru@multitest.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        Profile::create(['user_id' => $teacherUser->id, 'gender' => 'L']);
        $this->teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '19850101',
        ]);

        $this->companyA = Company::create([
            'school_id' => $this->school->id,
            'created_by_user_id' => $this->adminUser->id,
            'name' => 'PT Perusahaan A',
            'address' => 'Jakarta',
            'contact_person' => 'HRD A',
            'phone' => '0811111111',
            'work_days' => '5_days',
            'is_pkl_started' => true,
        ]);

        $this->companyB = Company::create([
            'school_id' => $this->school->id,
            'created_by_user_id' => $this->adminUser->id,
            'name' => 'PT Perusahaan B',
            'address' => 'Bandung',
            'contact_person' => 'HRD B',
            'phone' => '0822222222',
            'work_days' => '5_days',
            'is_pkl_started' => true,
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'PKL TA 2026/2027',
            'start_date' => '2026-12-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
        ]);
    }

    public function test_student_can_be_plotted_to_two_companies_in_same_period_without_overlap(): void
    {
        $this->actingAs($this->adminUser);

        // Penempatan 1: Des 2026 di PT A
        $res1 = $this->post(route('adminsekolah.plotting.store'), [
            'student_id' => $this->student->id,
            'company_id' => $this->companyA->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-31',
        ]);
        $res1->assertSessionHasNoErrors();

        // Penempatan 2: Jan 2027 di PT B
        $res2 = $this->post(route('adminsekolah.plotting.store'), [
            'student_id' => $this->student->id,
            'company_id' => $this->companyB->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'start_date' => '2027-01-01',
            'end_date' => '2027-01-31',
        ]);
        $res2->assertSessionHasNoErrors();

        $this->assertEquals(2, StudentPkl::where('student_id', $this->student->id)->where('pkl_period_id', $this->period->id)->count());

        // Penempatan 3 yang bertabrakan (15 Des - 15 Jan) harus ditolak
        $res3 = $this->post(route('adminsekolah.plotting.store'), [
            'student_id' => $this->student->id,
            'company_id' => $this->companyB->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'start_date' => '2026-12-15',
            'end_date' => '2027-01-15',
        ]);
        $res3->assertSessionHasErrors('student_id');
    }

    public function test_active_pkl_resolves_by_date_and_absence_evaluates_only_active_placement(): void
    {
        $spklA = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $this->companyA->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        $spklB = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $this->companyB->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'start_date' => '2027-01-01',
            'end_date' => '2027-01-31',
            'status' => 'active',
        ]);

        // Pada 10 Des 2026 (Kamis) -> aktif di PT A
        Carbon::setTestNow(Carbon::parse('2026-12-10 17:00:00'));
        $this->assertEquals($spklA->id, $this->student->resolveActivePkl()?->id);
        $this->artisan('vokatrack:evaluate-absence')->assertSuccessful();
        $this->assertTrue(Attendance::where('student_pkl_id', $spklA->id)->whereDate('date', '2026-12-10')->where('status', 'alfa')->exists());
        $this->assertFalse(Attendance::where('student_pkl_id', $spklB->id)->whereDate('date', '2026-12-10')->exists());

        // Pada 11 Jan 2027 (Senin) -> aktif di PT B
        Carbon::setTestNow(Carbon::parse('2027-01-11 17:00:00'));
        $this->assertEquals($spklB->id, $this->student->resolveActivePkl()?->id);
        $this->artisan('vokatrack:evaluate-absence')->assertSuccessful();
        $this->assertTrue(Attendance::where('student_pkl_id', $spklB->id)->whereDate('date', '2027-01-11')->where('status', 'alfa')->exists());
        $this->assertFalse(Attendance::where('student_pkl_id', $spklA->id)->whereDate('date', '2027-01-11')->exists());

        Carbon::setTestNow();
    }

    public function test_only_last_placement_can_be_graded_in_same_period(): void
    {
        $spklA = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $this->companyA->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        $spklB = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $this->companyB->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'start_date' => '2027-01-01',
            'end_date' => '2027-01-31',
            'status' => 'active',
        ]);

        $this->assertFalse($spklA->isAssessmentTarget());
        $this->assertTrue($spklB->isAssessmentTarget());

        $service = app(AssessmentService::class);

        // Penilaian di PT A harus ditolak
        $threw = false;
        try {
            $service->saveSupervisorAssessment($spklA, [
                'score_dudi_discipline' => 85,
                'score_dudi_technical' => 85,
                'score_dudi_initiative' => 85,
            ]);
        } catch (ValidationException $e) {
            $threw = true;
        }
        $this->assertTrue($threw, 'Penilaian pada penempatan pertama seharusnya ditolak.');

        // Penilaian di PT B (terakhir) berhasil
        $assessment = $service->saveSupervisorAssessment($spklB, [
            'score_dudi_discipline' => 90,
            'score_dudi_technical' => 88,
            'score_dudi_initiative' => 92,
        ]);
        $this->assertEquals($spklB->id, $assessment->student_pkl_id);

        // Scope onlyAssessmentTargets hanya mengambil PT B
        $targetIds = StudentPkl::onlyAssessmentTargets($this->school->id)->pluck('id')->all();
        $this->assertEquals([$spklB->id], $targetIds);

        // Sertifikat pada PT A (non-target) ditolak
        $certService = app(CertificateService::class);
        $certThrew = false;
        try {
            $certService->generateForStudentPkl($spklA);
        } catch (ValidationException $e) {
            $certThrew = true;
        }
        $this->assertTrue($certThrew, 'Penerbitan sertifikat untuk penempatan non-target seharusnya ditolak.');

        // Sertifikat pada PT B (target) berhasil
        $cert = $certService->generateForStudentPkl($spklB);
        $this->assertEquals($spklB->id, $cert->student_pkl_id);
    }

    public function test_parent_portal_and_pkl_map_respect_active_dates_and_custom_schedule(): void
    {
        $this->companyA->update([
            'latitude' => -6.2,
            'longitude' => 106.8,
            'work_days' => 'custom',
            'custom_work_days' => [1, 2, 4, 5, 6], // Rabu libur
        ]);
        $this->companyB->update([
            'latitude' => -6.9,
            'longitude' => 107.6,
        ]);

        $spklA = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $this->companyA->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

        $spklB = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $this->companyB->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'start_date' => '2027-01-01',
            'end_date' => '2027-01-31',
            'status' => 'active',
        ]);

        // Rabu 9 Des 2026: Parent portal menampilkan PT A, & Peta menandai PT A libur (gray), PT B tidak dianggap aktif (0 siswa)
        Carbon::setTestNow(Carbon::parse('2026-12-09 13:00:00'));
        $parentData = app(ParentMonitoringService::class)->getMonitoringData($this->student);
        $this->assertEquals($spklA->id, $parentData['spkl']?->id);

        $mapData = app(PklMapService::class)->getMapData($this->school->id, ['date' => '2026-12-09']);
        $markersByName = collect($mapData['markers'])->keyBy('name');
        $this->assertEquals('gray', $markersByName['PT Perusahaan A']['status']);
        $this->assertEquals(0, $markersByName['PT Perusahaan B']['total_students']);

        // Senin 11 Jan 2027: Parent portal otomatis beralih ke PT B
        Carbon::setTestNow(Carbon::parse('2027-01-11 10:00:00'));
        $parentDataJan = app(ParentMonitoringService::class)->getMonitoringData($this->student);
        $this->assertEquals($spklB->id, $parentDataJan['spkl']?->id);

        Carbon::setTestNow();
    }

    public function test_existing_placement_inherits_company_default_dates_for_second_company_plotting(): void
    {
        $this->actingAs($this->adminUser);

        // Penempatan awal (seperti hasil seeder) tanpa start_date/end_date eksplisit
        $spklA = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $this->companyA->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
        ]);

        // Admin mengatur Tanggal Mulai/Selesai DU/DI pada PT Perusahaan A menjadi Des 2026
        $this->put(route('adminsekolah.companies.update', $this->companyA), [
            'name' => $this->companyA->name,
            'address' => $this->companyA->address,
            'contact_person' => $this->companyA->contact_person,
            'phone' => $this->companyA->phone,
            'work_days' => '5_days',
            'default_start_date' => '2026-12-01',
            'default_end_date' => '2026-12-31',
        ])->assertSessionHasNoErrors();

        $spklA->refresh();
        $this->assertEquals('2026-12-01', $spklA->effective_start_date?->toDateString());
        $this->assertEquals('2026-12-31', $spklA->effective_end_date?->toDateString());

        // Sekarang siswa bisa langsung diplot ke PT Perusahaan B untuk Jan-Mar 2027 tanpa error tabrakan
        $resB = $this->post(route('adminsekolah.plotting.store'), [
            'student_id' => $this->student->id,
            'company_id' => $this->companyB->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'start_date' => '2027-01-01',
            'end_date' => '2027-03-31',
        ]);
        $resB->assertSessionHasNoErrors();

        $this->assertEquals(2, StudentPkl::where('student_id', $this->student->id)->count());
    }
}
