<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherDashboardAttendanceAuditTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected PklPeriod $period;
    protected Major $major;
    protected SchoolClass $class;
    protected Company $companyStarted;
    protected Company $companyNotStarted;
    protected Supervisor $supervisorA;
    protected Supervisor $supervisorB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '88887777',
            'name' => 'SMKN 2 Garut',
            'address' => 'Jl. Suherman',
            'phone' => '0262-123456',
            'email' => 'info@smkn2garut.sch.id',
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Bambang Guru, M.Pd.',
            'email' => 'bambang@smkn2garut.sch.id',
            'password' => 'password123',
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'nip' => '198001012005011001',
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Periode 2026/2027 Ganjil',
            'start_date' => Carbon::today()->subMonths(1)->toDateString(),
            'end_date' => Carbon::today()->addMonths(5)->toDateString(),
            'is_active' => true,
        ]);

        $this->major = Major::create([
            'school_id' => $this->school->id,
            'code' => 'GP',
            'name' => 'Teknik Geologi Pertambangan',
        ]);

        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->major->id,
            'name' => 'XII GP 1',
            'academic_year' => '2026/2027',
        ]);

        // Perusahaan 1: PKL Sudah Mulai (Pertamina)
        $this->companyStarted = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT. PERTAMINA GEOTHERMAL',
            'address' => 'Kamojang',
            'contact_person' => 'Wahyu Nugroho',
            'phone' => '022-7104523',
            'work_days' => '5_days',
            'is_pkl_started' => true,
        ]);

        $supUserA = User::create([
            'school_id' => $this->school->id,
            'name' => 'Supervisor Pertamina',
            'email' => 'sup.pertamina@example.com',
            'password' => 'password123',
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        $this->supervisorA = Supervisor::create([
            'user_id' => $supUserA->id,
            'company_id' => $this->companyStarted->id,
        ]);

        // Perusahaan 2: PKL Belum Mulai (PT. GEOSERVICES LTD)
        $this->companyNotStarted = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT. GEOSERVICES LTD',
            'address' => 'Jababeka Cikarang',
            'contact_person' => 'Ir. Dedi Supriyadi',
            'phone' => '021-8934567',
            'work_days' => '5_days',
            'is_pkl_started' => false,
        ]);

        $supUserB = User::create([
            'school_id' => $this->school->id,
            'name' => 'Supervisor Geoservices',
            'email' => 'sup.geoservices@example.com',
            'password' => 'password123',
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        $this->supervisorB = Supervisor::create([
            'user_id' => $supUserB->id,
            'company_id' => $this->companyNotStarted->id,
        ]);
    }

    protected function createStudentPkl(string $name, Company $company, Supervisor $supervisor): StudentPkl
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)) . '@siswa.smkn2garut.sch.id',
            'password' => 'password123',
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'class_id' => $this->class->id,
            'nis' => (string) rand(10000, 99999),
            'nisn' => (string) rand(1000000000, 9999999999),
        ]);

        return StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'teacher_id' => $this->teacher->id,
            'supervisor_id' => $supervisor->id,
            'pkl_period_id' => $this->period->id,
            'start_date' => Carbon::today()->subMonths(1)->toDateString(),
            'end_date' => Carbon::today()->addMonths(5)->toDateString(),
            'status' => 'active',
        ]);
    }

    public function test_teacher_dashboard_accurately_handles_unstarted_pkl_and_checkout_breakdown(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 17, 10, 0)); // Wednesday 17:10 WIB

        // 4 Siswa Pertamina (Sudah Mulai PKL)
        $spklStarted1 = $this->createStudentPkl('Carrel Munjid', $this->companyStarted, $this->supervisorA);
        $spklStarted2 = $this->createStudentPkl('Kennino Farras', $this->companyStarted, $this->supervisorA);
        $spklStarted3 = $this->createStudentPkl('Rival Kartono', $this->companyStarted, $this->supervisorA);
        $spklStarted4 = $this->createStudentPkl('Muhammad Adzka', $this->companyStarted, $this->supervisorA);

        // 8 Siswa Geoservices (Belum Mulai PKL)
        for ($i = 1; $i <= 8; $i++) {
            $this->createStudentPkl("Siswa Belum Mulai {$i}", $this->companyNotStarted, $this->supervisorB);
        }

        // Ke-4 siswa Pertamina hadir dan sudah pulang
        foreach ([$spklStarted1, $spklStarted2, $spklStarted3, $spklStarted4] as $spkl) {
            Attendance::create([
                'school_id' => $this->school->id,
                'student_pkl_id' => $spkl->id,
                'date' => Carbon::today()->toDateString(),
                'status' => 'hadir',
                'checkin_time' => '12:50:00',
                'checkout_time' => '16:22:00',
                'is_within_radius' => true,
            ]);
        }

        $response = $this->actingAs($this->teacherUser)->get(route('guru.dashboard'));

        $response->assertStatus(200);

        // Assert Controller Variables
        $response->assertViewHas('expectedToday', 4); // 12 total - 8 belum mulai = 4
        $response->assertViewHas('todayPresentCount', 4);
        $response->assertViewHas('checkedOutCount', 4);
        $response->assertViewHas('stillAtOfficeCount', 0);
        $response->assertViewHas('belumMulaiCount', 8);

        $todaySummary = $response->viewData('todaySummary');
        $this->assertEquals(0, $todaySummary['belum']);
        $this->assertEquals(8, $todaySummary['belum_mulai']);
        $this->assertEquals(4, $todaySummary['hadir']);

        // Assert rendered HTML
        $content = $response->getContent();

        // 1. Gauge: 100% Hadir, 4/4
        $this->assertStringContainsString('100%', $content);
        $this->assertStringContainsString('/4', $content);

        // 2. Subtext must show: semua sudah pulang (4) · 8 belum mulai PKL
        $this->assertStringContainsString('semua sudah pulang (4)', $content);
        $this->assertStringContainsString('8 belum mulai PKL', $content);

        // 3. Must NOT show wrong phrase "0 belum · 4 sudah pulang"
        $this->assertStringNotContainsString('0 belum · 4 sudah pulang', $content);

        // 4. Must display badge 'Belum Mulai PKL'
        $this->assertStringContainsString('Belum Mulai PKL', $content);

        // 5. Must NOT display badge 'Belum Presensi' for students whose PKL hasn't started
        $this->assertStringNotContainsString('Belum Presensi', $content);

        // 6. Pill must contain Belum Mulai with 8
        $this->assertStringContainsString('Belum Mulai', $content);

        Carbon::setTestNow();
    }

    public function test_teacher_dashboard_checkout_breakdown_when_some_students_still_at_office(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 17, 10, 0)); // Wednesday 17:10 WIB

        // 8 Siswa di perusahaan yang sudah mulai PKL
        $spkls = [];
        for ($i = 1; $i <= 8; $i++) {
            $spkls[] = $this->createStudentPkl("Siswa Aktif {$i}", $this->companyStarted, $this->supervisorA);
        }

        // 6 siswa sudah checkout, 2 siswa belum checkout (masih di lokasi)
        foreach ($spkls as $index => $spkl) {
            $checkoutTime = ($index < 6) ? '16:15:00' : null;
            Attendance::create([
                'school_id' => $this->school->id,
                'student_pkl_id' => $spkl->id,
                'date' => Carbon::today()->toDateString(),
                'status' => 'hadir',
                'checkin_time' => '07:55:00',
                'checkout_time' => $checkoutTime,
                'is_within_radius' => true,
            ]);
        }

        $response = $this->actingAs($this->teacherUser)->get(route('guru.dashboard'));

        $response->assertStatus(200);

        // Assert Controller Variables
        $response->assertViewHas('expectedToday', 8);
        $response->assertViewHas('todayPresentCount', 8);
        $response->assertViewHas('checkedOutCount', 6);
        $response->assertViewHas('stillAtOfficeCount', 2);

        $content = $response->getContent();

        // Subtext must display "6 sudah pulang · 2 belum pulang"
        $this->assertStringContainsString('6 sudah pulang · 2 belum pulang', $content);

        // Must NOT show contradictory "0 belum · 6 sudah pulang"
        $this->assertStringNotContainsString('0 belum · 6 sudah pulang', $content);

        Carbon::setTestNow();
    }
}
