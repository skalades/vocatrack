<?php

namespace Tests\Feature;

use App\Console\Commands\EvaluateDailyAbsence;
use App\Models\Attendance;
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
use App\Services\HolidayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidayWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMK Negeri 1 Test',
            'email' => 'smkn1@test.sch.id',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '021-998877',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah',
            'email' => 'admin@smkn1test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
    }

    public function test_admin_sekolah_can_create_and_sync_holidays(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Visit Holidays Page
        $response = $this->get(route('adminsekolah.holidays'));
        $response->assertStatus(200);
        $response->assertSee('Kalender Hari Libur');

        // 2. Create custom holiday
        $postResponse = $this->post(route('adminsekolah.holidays.store'), [
            'title' => 'Libur Ujian Sekolah',
            'holiday_date' => '2026-04-15',
            'description' => 'Seluruh siswa kelas XII libur',
            'is_national' => 0,
        ]);
        $postResponse->assertRedirect();
        $this->assertDatabaseHas('school_holidays', [
            'school_id' => $this->school->id,
            'title' => 'Libur Ujian Sekolah',
            'is_national' => 0,
        ]);

        // 3. Sync national holidays for 2026
        $syncResponse = $this->post(route('adminsekolah.holidays.sync-national'), [
            'year' => 2026,
        ]);
        $syncResponse->assertRedirect();
        $this->assertDatabaseHas('school_holidays', [
            'school_id' => $this->school->id,
            'title' => 'Tahun Baru Masehi',
            'is_national' => 1,
        ]);
    }

    public function test_evaluate_absence_skips_student_on_registered_holiday(): void
    {
        Carbon::setTestNow('2026-08-17 23:59:00'); // Hari Kemerdekaan RI (Monday)

        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing',
            'email' => 'guru@smkn1test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'school_id' => $this->school->id,
            'nip' => '198001012010011001',
        ]);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa PKL Test',
            'email' => 'siswa@smkn1test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $major = Major::create(['school_id' => $this->school->id, 'name' => 'RPL', 'code' => 'RPL']);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'major_id' => $major->id, 'name' => 'XII RPL 1', 'grade_level' => 'XII', 'academic_year' => '2026/2027']);
        $student = Student::create(['user_id' => $studentUser->id, 'class_id' => $class->id, 'nisn' => '1234567890', 'nis' => '12345']);

        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Teknologi Unggul',
            'address' => 'Jl. Sudirman',
            'contact_person' => 'Budi',
            'phone' => '08123456789',
            'work_days' => '5_days',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
        ]);

        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Semester Gasal 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'teacher_id' => $teacher->id,
            'pkl_period_id' => $period->id,
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        // Register 2026-08-17 as school holiday
        SchoolHoliday::create([
            'school_id' => $this->school->id,
            'title' => 'Hari Kemerdekaan RI',
            'holiday_date' => '2026-08-17',
            'is_national' => true,
        ]);

        // Run evaluate command
        $this->artisan('vokatrack:evaluate-absence')
            ->assertSuccessful();

        // Student should NOT be marked as ALFA because today is holiday
        $this->assertDatabaseMissing('attendances', [
            'student_pkl_id' => $spkl->id,
            'date' => '2026-08-17',
            'status' => 'alfa',
        ]);

        Carbon::setTestNow(); // reset
    }
}
