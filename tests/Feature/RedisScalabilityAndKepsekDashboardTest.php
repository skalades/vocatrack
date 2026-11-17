<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\SupervisionVisit;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RedisScalabilityAndKepsekDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $kepsekUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '66554433',
            'name' => 'SMK Negeri 1 Skalabilitas',
            'address' => 'Jl. Metro No. 20',
            'phone' => '021-554433',
            'email' => 'info@smk1skala.sch.id',
            'is_active' => true,
        ]);

        $this->kepsekUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Dr. H. Bambang Hartono, M.Pd.',
            'email' => 'kepsek@smk1skala.sch.id',
            'password' => Hash::make('password123'),
            'role' => 'kepala-sekolah',
            'is_active' => true,
        ]);
    }

    public function test_redis_configuration_and_client_support(): void
    {
        $redisConfig = config('database.redis');
        $this->assertIsArray($redisConfig);
        $this->assertContains($redisConfig['client'], ['phpredis', 'predis']);
        $this->assertSame(3, $redisConfig['default']['max_retries']);

        $cacheConfig = config('cache.stores.redis');
        $this->assertNotNull($cacheConfig);
        $this->assertSame('redis', $cacheConfig['driver']);

        $queueConfig = config('queue.connections.redis');
        $this->assertNotNull($queueConfig);
        $this->assertSame('redis', $queueConfig['driver']);
    }

    public function test_kepsek_dashboard_renders_weekly_trend_and_student_insights(): void
    {
        $major = Major::create([
            'school_id' => $this->school->id,
            'name' => 'Desain Komunikasi Visual',
            'code' => 'DKV',
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII DKV 1',
            'academic_year' => '2026/2027',
        ]);

        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Studio Kreatif Digital',
            'address' => 'Jl. Desain No. 1',
            'contact_person' => 'Pak Joko',
            'phone' => '081211112222',
        ]);

        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Gasal 2026',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(2),
            'is_active' => true,
        ]);

        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Supervisi',
            'email' => 'guru.supervisi@smk1skala.sch.id',
            'password' => Hash::make('password123'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '197501012000031001',
        ]);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Citra Lestari',
            'email' => 'citra@smk1skala.sch.id',
            'password' => Hash::make('password123'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'nis' => '99881',
            'nisn' => '0077123456',
        ]);

        $spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);

        // Catatan presensi: 1 hadir, 2 alfa
        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $spkl->id,
            'date' => Carbon::today()->subDays(2)->toDateString(),
            'status' => 'hadir',
        ]);

        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $spkl->id,
            'date' => Carbon::today()->subDay()->toDateString(),
            'status' => 'alfa',
        ]);

        // Kunjungan supervisi
        SupervisionVisit::create([
            'school_id' => $this->school->id,
            'teacher_id' => $teacher->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'stage' => 'monitoring_1',
            'visit_date' => Carbon::today()->toDateString(),
            'student_progress_summary' => 'Progres pembuatan aset grafis memuaskan.',
            'company_feedback' => 'Disiplin dan komunikasi sangat baik.',
        ]);

        $response = $this->actingAs($this->kepsekUser)->get(route('kepsek.dashboard'));

        $response->assertOk();
        $response->assertSee('Tren Kehadiran 7 Hari Terakhir');
        $response->assertSee('Siswa Butuh Perhatian');
        $response->assertSee('Citra Lestari');
        $response->assertSee('Kunjungan Supervisi Guru:');
    }
}
