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
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyCustomScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected User $kajurUser;
    protected Major $major;
    protected Teacher $teacher;
    protected Student $student;
    protected PklPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '20199911',
            'name' => 'SMK Negeri Jadwal Custom',
            'address' => 'Jl. Pendidikan No. 7',
            'phone' => '021-77665544',
            'email' => 'smkcustom@test.sch.id',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah',
            'email' => 'admin@customtest.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
        Profile::create(['user_id' => $this->adminUser->id, 'gender' => 'L']);

        $this->kajurUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Pak Kajur',
            'email' => 'kajur@customtest.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        Profile::create(['user_id' => $this->kajurUser->id, 'gender' => 'L']);

        $this->teacher = Teacher::create([
            'user_id' => $this->kajurUser->id,
            'nip' => '19800101',
        ]);

        $this->major = Major::create([
            'school_id' => $this->school->id,
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
            'program_duration_years' => 3,
            'default_pkl_duration_months' => 6,
            'head_teacher_id' => $this->teacher->id,
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->major->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siti Aminah',
            'email' => 'siti@customtest.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Profile::create(['user_id' => $studentUser->id, 'gender' => 'P']);
        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'nis' => '2001',
            'nisn' => '0099887766',
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'PKL 2026/2027',
            'start_date' => '2026-12-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_store_and_render_custom_work_days_and_hours(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('adminsekolah.companies.store'), [
            'name' => 'PT Jadwal Unik',
            'address' => 'Jl. Merdeka No. 1',
            'contact_person' => 'Bapak Andi',
            'phone' => '08123456789',
            'work_days' => 'custom',
            'custom_work_days' => [1, 2, 4, 5, 6], // Sen, Sel, Kam, Jum, Sab (Rabu & Minggu libur)
            'override_hours' => '1',
            'checkin_start' => '08:30',
            'checkin_late' => '09:30',
            'checkin_alfa' => '12:00',
            'checkout_start' => '17:00',
            'major_ids' => [$this->major->id],
        ]);
        $response->assertSessionHasNoErrors();

        $company = Company::where('name', 'PT Jadwal Unik')->firstOrFail();
        $this->assertEquals('custom', $company->work_days);
        $this->assertEquals([1, 2, 4, 5, 6], $company->custom_work_days);
        $this->assertTrue($company->override_hours);
        $this->assertEquals('09:30:00', $company->custom_work_hours['checkin_late']);
        $this->assertEquals('Custom (Sen, Sel, Kam, Jum, Sab)', $company->work_days_label);

        $this->get(route('adminsekolah.companies'))->assertOk()->assertSee('PT Jadwal Unik');
    }

    public function test_kajur_can_store_update_and_render_custom_work_days_and_hours(): void
    {
        $this->actingAs($this->kajurUser);

        $resStore = $this->post(route('kajur.companies.store'), [
            'name' => 'CV Shift Kajur',
            'address' => 'Jl. Teknologi No. 5',
            'contact_person' => 'Ibu Rina',
            'phone' => '08199887766',
            'work_days' => 'custom',
            'custom_work_days' => [1, 2, 4, 5, 6],
            'override_hours' => '1',
            'checkin_start' => '09:00',
            'checkin_late' => '10:00',
            'checkin_alfa' => '12:30',
            'checkout_start' => '18:00',
        ]);
        $resStore->assertSessionHasNoErrors();

        $company = Company::where('name', 'CV Shift Kajur')->firstOrFail();
        $this->assertTrue($company->override_hours);
        $this->assertEquals([1, 2, 4, 5, 6], $company->custom_work_days);

        // Update kembali menjadi 6_days tanpa jam khusus
        $resUpdate = $this->put(route('kajur.companies.update', $company), [
            'name' => 'CV Shift Kajur',
            'address' => 'Jl. Teknologi No. 5',
            'contact_person' => 'Ibu Rina',
            'phone' => '08199887766',
            'work_days' => '6_days',
            'override_hours' => '0',
        ]);
        $resUpdate->assertSessionHasNoErrors();

        $company->refresh();
        $this->assertEquals('6_days', $company->work_days);
        $this->assertNull($company->custom_work_days);
        $this->assertFalse($company->override_hours);
        $this->assertNull($company->custom_work_hours);

        $this->get(route('kajur.companies'))->assertOk()->assertSee('CV Shift Kajur');
    }

    public function test_custom_work_days_validation_rejects_empty_days(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('adminsekolah.companies.store'), [
            'name' => 'PT Invalid Custom',
            'address' => 'Jl. Salah No. 2',
            'contact_person' => 'PIC',
            'phone' => '0811223344',
            'work_days' => 'custom',
            'custom_work_days' => [],
        ]);

        $response->assertSessionHasErrors('custom_work_days');
    }

    public function test_attendance_and_absence_respect_custom_work_days_and_custom_hours(): void
    {
        $company = Company::create([
            'school_id' => $this->school->id,
            'created_by_user_id' => $this->adminUser->id,
            'name' => 'PT Rabu Libur',
            'address' => 'Surabaya',
            'contact_person' => 'HRD',
            'phone' => '08123456',
            'work_days' => 'custom',
            'custom_work_days' => [1, 2, 4, 5, 6], // Sen, Sel, Kam, Jum, Sab (Rabu & Minggu libur)
            'override_hours' => true,
            'custom_work_hours' => [
                'checkin_start' => '08:30:00',
                'checkin_late' => '09:30:00',
                'checkin_alfa' => '12:00:00',
                'checkout_start' => '17:00:00',
            ],
            'is_pkl_started' => true,
        ]);

        $spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'start_date' => '2026-12-01',
            'end_date' => '2027-01-31',
            'status' => 'active',
        ]);

        $attendanceService = app(AttendanceService::class);

        // Rabu 2026-12-09 -> libur perusahaan (tidak di-alfa)
        Carbon::setTestNow(Carbon::parse('2026-12-09 18:00:00'));
        $this->assertFalse($company->isWorkDay());
        $statusRabu = $attendanceService->getDayAttendanceStatus($spkl);
        $this->assertFalse($statusRabu['is_work_day']);
        $this->artisan('vokatrack:evaluate-absence')->assertSuccessful();
        $this->assertFalse(Attendance::where('student_pkl_id', $spkl->id)->whereDate('date', '2026-12-09')->exists());

        // Kamis 2026-12-10 pukul 09:15 -> masuk hari kerja, & masih hadir (bukan terlambat karena batas telat 09:30)
        Carbon::setTestNow(Carbon::parse('2026-12-10 09:15:00'));
        $this->assertTrue($company->isWorkDay());
        $attendance = $attendanceService->recordCheckIn($spkl, []);
        $this->assertEquals('hadir', $attendance->status);

        // Sabtu 2026-12-12 -> hari kerja aktif, jika tidak absen tercatat alfa
        Carbon::setTestNow(Carbon::parse('2026-12-12 18:00:00'));
        $this->assertTrue($company->isWorkDay());
        $this->artisan('vokatrack:evaluate-absence')->assertSuccessful();
        $this->assertTrue(Attendance::where('student_pkl_id', $spkl->id)->whereDate('date', '2026-12-12')->where('status', 'alfa')->exists());

        // Minggu 2026-12-13 -> libur perusahaan (tidak di-alfa)
        Carbon::setTestNow(Carbon::parse('2026-12-13 18:00:00'));
        $this->assertFalse($company->isWorkDay());
        $this->artisan('vokatrack:evaluate-absence')->assertSuccessful();
        $this->assertFalse(Attendance::where('student_pkl_id', $spkl->id)->whereDate('date', '2026-12-13')->exists());

        Carbon::setTestNow();
    }
}
