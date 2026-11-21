<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolHoliday;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyWorkDaysSevenDaysTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected Teacher $teacher;
    protected Major $major;
    protected SchoolClass $class;
    protected PklPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '88889999',
            'name' => 'SMK Negeri Pertambangan & Proyek',
            'email' => 'smk@proyek.sch.id',
            'address' => 'Jl. Tambang No. 10',
            'phone' => '021-778899',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah',
            'email' => 'admin@proyek.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing',
            'email' => 'guru@proyek.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->teacher = Teacher::create(['user_id' => $teacherUser->id, 'nip' => '198001012005011001']);

        $this->major = Major::create(['school_id' => $this->school->id, 'name' => 'Geologi Pertambangan', 'code' => 'GP']);
        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->major->id,
            'name' => 'XII GP 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Tahun Ajaran 2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
    }

    public function test_admin_sekolah_can_create_and_update_company_with_seven_days_work_days(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create company with 7_days
        $response = $this->post(route('adminsekolah.companies.store'), [
            'name' => 'PT Sangga Buana Nusantara',
            'address' => 'Site Proyek Tambang Blok B',
            'contact_person' => 'Bambang Setyawan, S.T.',
            'phone' => '021-78839001',
            'work_days' => '7_days',
            'is_pkl_started' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('companies', [
            'name' => 'PT Sangga Buana Nusantara',
            'work_days' => '7_days',
        ]);

        $company = Company::where('name', 'PT Sangga Buana Nusantara')->first();
        $this->assertEquals('7 Hari (On-Project)', $company->work_days_label);
        $this->assertTrue($company->worksOnHolidays());

        // 2. Later on, company gives leniency and changes to 5_days or 6_days
        $updateResponse = $this->put(route('adminsekolah.companies.update', $company), [
            'name' => 'PT Sangga Buana Nusantara',
            'address' => 'Site Proyek Tambang Blok B',
            'contact_person' => 'Bambang Setyawan, S.T.',
            'phone' => '021-78839001',
            'work_days' => '6_days',
            'is_pkl_started' => true,
        ]);

        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'work_days' => '6_days',
        ]);

        $company->refresh();
        $this->assertFalse($company->worksOnHolidays());
    }

    public function test_company_is_work_day_helper(): void
    {
        $company7 = Company::create([
            'school_id' => $this->school->id,
            'name' => 'Company 7 Days',
            'address' => 'Location',
            'contact_person' => 'PIC',
            'phone' => '0812',
            'work_days' => '7_days',
        ]);

        $company6 = Company::create([
            'school_id' => $this->school->id,
            'name' => 'Company 6 Days',
            'address' => 'Location',
            'contact_person' => 'PIC',
            'phone' => '0812',
            'work_days' => '6_days',
        ]);

        $company5 = Company::create([
            'school_id' => $this->school->id,
            'name' => 'Company 5 Days',
            'address' => 'Location',
            'contact_person' => 'PIC',
            'phone' => '0812',
            'work_days' => '5_days',
        ]);

        // 2026-11-22 is a Sunday (dayOfWeekIso = 7)
        $sunday = Carbon::parse('2026-11-22');
        // 2026-11-21 is a Saturday (dayOfWeekIso = 6)
        $saturday = Carbon::parse('2026-11-21');
        // 2026-11-20 is a Friday (dayOfWeekIso = 5)
        $friday = Carbon::parse('2026-11-20');

        // 7 days company works on all 7 days
        $this->assertTrue($company7->isWorkDay($friday));
        $this->assertTrue($company7->isWorkDay($saturday));
        $this->assertTrue($company7->isWorkDay($sunday));
        $this->assertTrue($company7->worksOnHolidays());

        // 6 days company works Mon-Sat, Sunday off
        $this->assertTrue($company6->isWorkDay($friday));
        $this->assertTrue($company6->isWorkDay($saturday));
        $this->assertFalse($company6->isWorkDay($sunday));
        $this->assertFalse($company6->worksOnHolidays());

        // 5 days company works Mon-Fri, Sat-Sun off
        $this->assertTrue($company5->isWorkDay($friday));
        $this->assertFalse($company5->isWorkDay($saturday));
        $this->assertFalse($company5->isWorkDay($sunday));
        $this->assertFalse($company5->worksOnHolidays());
    }

    public function test_evaluate_daily_absence_marks_alfa_on_sunday_for_7_days_company_if_not_checked_in(): void
    {
        // Set Sunday: 2026-11-22
        Carbon::setTestNow(Carbon::parse('2026-11-22 23:55:00'));

        $company7 = Company::create([
            'school_id' => $this->school->id,
            'name' => 'Mining Site 24/7',
            'address' => 'Jl. Tambang',
            'contact_person' => 'Bambang',
            'phone' => '0811111111',
            'work_days' => '7_days',
            'is_pkl_started' => true,
        ]);

        $company6 = Company::create([
            'school_id' => $this->school->id,
            'name' => 'Office 6 Days',
            'address' => 'Jl. Kantor',
            'contact_person' => 'Siti',
            'phone' => '0822222222',
            'work_days' => '6_days',
            'is_pkl_started' => true,
        ]);

        // Student A at 7-day company
        $userA = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa On Project',
            'email' => 'siswaA@proyek.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $studentA = Student::create(['user_id' => $userA->id, 'class_id' => $this->class->id, 'nisn' => '1001', 'nis' => '1001']);
        $spklA = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $studentA->id,
            'company_id' => $company7->id,
            'teacher_id' => $this->teacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        // Student B at 6-day company
        $userB = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Normal',
            'email' => 'siswaB@proyek.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $studentB = Student::create(['user_id' => $userB->id, 'class_id' => $this->class->id, 'nisn' => '1002', 'nis' => '1002']);
        $spklB = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $studentB->id,
            'company_id' => $company6->id,
            'teacher_id' => $this->teacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $this->artisan('vokatrack:evaluate-absence')->assertSuccessful();

        // Student A should be ALFA because company works 7 days full
        $attendanceA = Attendance::where('student_pkl_id', $spklA->id)->whereDate('date', '2026-11-22')->first();
        $this->assertNotNull($attendanceA);
        $this->assertEquals('alfa', $attendanceA->status);

        // Student B should NOT be alfa because Sunday is an off-day for 6-day company
        $attendanceB = Attendance::where('student_pkl_id', $spklB->id)->whereDate('date', '2026-11-22')->first();
        $this->assertNull($attendanceB);

        Carbon::setTestNow();
    }

    public function test_student_in_7_days_company_must_work_even_on_national_holiday_or_marked_alfa(): void
    {
        // Set Wednesday: 2026-11-18 (Tanggal merah / National Holiday)
        Carbon::setTestNow(Carbon::parse('2026-11-18 23:55:00'));

        $company7 = Company::create([
            'school_id' => $this->school->id,
            'name' => 'Mining Site 24/7',
            'address' => 'Jl. Tambang',
            'contact_person' => 'Bambang',
            'phone' => '0811111111',
            'work_days' => '7_days',
            'is_pkl_started' => true,
        ]);

        $company5 = Company::create([
            'school_id' => $this->school->id,
            'name' => 'Office 5 Days',
            'address' => 'Jl. Kantor',
            'contact_person' => 'Siti',
            'phone' => '0822222222',
            'work_days' => '5_days',
            'is_pkl_started' => true,
        ]);

        // Student A at 7-day company
        $userA = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa On Project',
            'email' => 'siswaA_holiday@proyek.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $studentA = Student::create(['user_id' => $userA->id, 'class_id' => $this->class->id, 'nisn' => '2001', 'nis' => '2001']);
        $spklA = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $studentA->id,
            'company_id' => $company7->id,
            'teacher_id' => $this->teacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        // Student B at 5-day company
        $userB = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Kantor 5 Hari',
            'email' => 'siswaB_holiday@proyek.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $studentB = Student::create(['user_id' => $userB->id, 'class_id' => $this->class->id, 'nisn' => '2002', 'nis' => '2002']);
        $spklB = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $studentB->id,
            'company_id' => $company5->id,
            'teacher_id' => $this->teacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        // Register 2026-11-18 as a school/national holiday
        SchoolHoliday::create([
            'school_id' => $this->school->id,
            'title' => 'Hari Libur Nasional Resmi',
            'holiday_date' => '2026-11-18',
            'is_national' => true,
        ]);

        $this->artisan('vokatrack:evaluate-absence')->assertSuccessful();

        // Student A at 7-day company MUST STILL WORK even on national holiday,
        // so if they didn't check in, they are marked ALFA!
        $attendanceA = Attendance::where('student_pkl_id', $spklA->id)->whereDate('date', '2026-11-18')->first();
        $this->assertNotNull($attendanceA);
        $this->assertEquals('alfa', $attendanceA->status);

        // Student B at 5-day company gets the national holiday off,
        // so they are NOT marked ALFA!
        $attendanceB = Attendance::where('student_pkl_id', $spklB->id)->whereDate('date', '2026-11-18')->first();
        $this->assertNull($attendanceB);

        Carbon::setTestNow();
    }

    public function test_leniency_can_be_granted_by_changing_to_6_days_and_holidays_apply_again(): void
    {
        // Given a company that initially had 7_days
        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'Project Company',
            'address' => 'Site A',
            'contact_person' => 'Manager',
            'phone' => '081234',
            'work_days' => '7_days',
            'is_pkl_started' => true,
        ]);

        $user = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Fleksibel',
            'email' => 'siswa_flex@proyek.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create(['user_id' => $user->id, 'class_id' => $this->class->id, 'nisn' => '3001', 'nis' => '3001']);
        $spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'teacher_id' => $this->teacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        // Register 2026-11-18 as a national holiday
        SchoolHoliday::create([
            'school_id' => $this->school->id,
            'title' => 'Hari Libur Nasional',
            'holiday_date' => '2026-11-18',
            'is_national' => true,
        ]);

        // Now, due to leniency / project ending, admin changes company to 6_days
        $company->update(['work_days' => '6_days']);
        $this->assertFalse($company->worksOnHolidays());

        // Run evaluate absence on the holiday
        Carbon::setTestNow(Carbon::parse('2026-11-18 23:55:00'));
        $this->artisan('vokatrack:evaluate-absence')->assertSuccessful();

        // With 6_days, the student now enjoys national holidays off (NOT marked alfa)
        $attendanceHoliday = Attendance::where('student_pkl_id', $spkl->id)->whereDate('date', '2026-11-18')->first();
        $this->assertNull($attendanceHoliday);

        // Also on Sunday, student enjoys weekend off (NOT marked alfa)
        Carbon::setTestNow(Carbon::parse('2026-11-22 23:55:00')); // Sunday
        $this->artisan('vokatrack:evaluate-absence')->assertSuccessful();

        $attendanceSunday = Attendance::where('student_pkl_id', $spkl->id)->whereDate('date', '2026-11-22')->first();
        $this->assertNull($attendanceSunday);

        Carbon::setTestNow();
    }
}
