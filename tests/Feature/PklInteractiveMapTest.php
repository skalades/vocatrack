<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PklInteractiveMapTest extends TestCase
{
    use RefreshDatabase;

    protected School $schoolA;
    protected School $schoolB;

    protected User $adminA;
    protected User $kepsekA;
    protected User $teacherAUser;
    protected Teacher $teacherA;

    protected Major $majorRpl;
    protected SchoolClass $classRpl;

    protected Company $companyGreen;
    protected Company $companyYellow;
    protected Company $companyRed;

    protected Student $student1;
    protected Student $student2;
    protected Student $student3;

    protected StudentPkl $spkl1;
    protected StudentPkl $spkl2;
    protected StudentPkl $spkl3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolA = School::create([
            'name' => 'SMKN 1 Bandung',
            'npsn' => '30303030',
            'email' => 'smkn1bdg@test.sch.id',
            'address' => 'Jl. Wastukencana No. 1',
            'phone' => '022-123456',
            'is_active' => true,
        ]);

        $this->adminA = User::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Admin SMKN 1',
            'email' => 'admin@smkn1bdg.test',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $this->kepsekA = User::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Drs. H. Mulyana, M.Pd',
            'email' => 'kepsek@smkn1bdg.test',
            'password' => bcrypt('password'),
            'role' => 'kepala-sekolah',
            'is_active' => true,
        ]);

        $this->teacherAUser = User::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Guru Pembimbing Rudi',
            'email' => 'rudi.guru@smkn1bdg.test',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);

        $this->teacherA = Teacher::create([
            'user_id' => $this->teacherAUser->id,
            'nip' => '198801012015011002',
        ]);

        $this->majorRpl = Major::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
        ]);

        $this->classRpl = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'major_id' => $this->majorRpl->id,
            'name' => 'XII RPL 1',
            'academic_year' => '2026/2027',
        ]);

        $period = PklPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Periode PKL 2026',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'is_active' => true,
        ]);

        // 1. Company Green (PT Solusi Digital - All present)
        $this->companyGreen = Company::create([
            'school_id' => $this->schoolA->id,
            'name' => 'PT Solusi Digital Prima',
            'address' => 'Jl. Dago No. 100 Bandung',
            'contact_person' => 'Budi HRD',
            'phone' => '022-111111',
            'latitude' => -6.8905,
            'longitude' => 107.6102,
            'radius_meters' => 100,
        ]);

        // 2. Company Yellow (PT Kreasi Media - Late/Leave)
        $this->companyYellow = Company::create([
            'school_id' => $this->schoolA->id,
            'name' => 'PT Kreasi Media Mandiri',
            'address' => 'Jl. Riau No. 50 Bandung',
            'contact_person' => 'Siti HRD',
            'phone' => '022-222222',
            'latitude' => -6.9080,
            'longitude' => 107.6180,
            'radius_meters' => 120,
        ]);

        // 3. Company Red (PT Industri Presisi - Alfa/Incident)
        $this->companyRed = Company::create([
            'school_id' => $this->schoolA->id,
            'name' => 'PT Industri Presisi Jawa',
            'address' => 'Jl. Soekarno Hatta No. 200 Bandung',
            'contact_person' => 'Joko HRD',
            'phone' => '022-333333',
            'latitude' => -6.9450,
            'longitude' => 107.6400,
            'radius_meters' => 150,
        ]);

        // Students
        $u1 = User::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Andi Wijaya',
            'email' => 'andi@student.test',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $this->student1 = Student::create([
            'user_id' => $u1->id,
            'class_id' => $this->classRpl->id,
            'nis' => '2001',
            'nisn' => '0020010001',
        ]);

        $u2 = User::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Bella Safira',
            'email' => 'bella@student.test',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $this->student2 = Student::create([
            'user_id' => $u2->id,
            'class_id' => $this->classRpl->id,
            'nis' => '2002',
            'nisn' => '0020010002',
        ]);

        $u3 = User::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Candra Darmawan',
            'email' => 'candra@student.test',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $this->student3 = Student::create([
            'user_id' => $u3->id,
            'class_id' => $this->classRpl->id,
            'nis' => '2003',
            'nisn' => '0020010003',
        ]);

        // Student PKL Plottings
        $this->spkl1 = StudentPkl::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->student1->id,
            'company_id' => $this->companyGreen->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $this->teacherA->id,
            'status' => 'active',
        ]);

        $this->spkl2 = StudentPkl::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->student2->id,
            'company_id' => $this->companyYellow->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $this->teacherA->id,
            'status' => 'active',
        ]);

        $this->spkl3 = StudentPkl::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->student3->id,
            'company_id' => $this->companyRed->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $this->teacherA->id,
            'status' => 'active',
        ]);

        // Today Attendance setups:
        $today = Carbon::today()->toDateString();

        // Student 1: Hadir
        Attendance::create([
            'school_id' => $this->schoolA->id,
            'student_pkl_id' => $this->spkl1->id,
            'date' => $today,
            'status' => 'hadir',
            'checkin_time' => '07:15:00',
            'is_within_radius' => true,
        ]);

        // Student 2: Terlambat
        Attendance::create([
            'school_id' => $this->schoolA->id,
            'student_pkl_id' => $this->spkl2->id,
            'date' => $today,
            'status' => 'terlambat',
            'checkin_time' => '08:25:00',
            'is_within_radius' => true,
        ]);

        // Student 3: Alfa
        Attendance::create([
            'school_id' => $this->schoolA->id,
            'student_pkl_id' => $this->spkl3->id,
            'date' => $today,
            'status' => 'alfa',
        ]);

        // Setup School B
        $this->schoolB = School::create([
            'name' => 'SMKN 2 Cirebon',
            'npsn' => '40404040',
            'email' => 'smkn2crb@test.sch.id',
            'address' => 'Jl. Kartini No. 5',
            'phone' => '0231-112233',
            'is_active' => true,
        ]);
    }

    public function test_admin_sekolah_can_view_interactive_map_with_pins_and_indicators(): void
    {
        $response = $this->actingAs($this->adminA)
            ->get(route('adminsekolah.map'));

        $response->assertStatus(200);
        $response->assertSee('Peta Sebaran Mitra DU/DI');
        $response->assertSee('PT Solusi Digital Prima');
        $response->assertSee('PT Kreasi Media Mandiri');
        $response->assertSee('PT Industri Presisi Jawa');

        // Verify status colors and labels in view data
        $mapData = $response->viewData('mapData');
        $this->assertCount(3, $mapData['markers']);

        $markersByStatus = collect($mapData['markers'])->keyBy('name');

        // PT Solusi Digital Prima -> Green (All Hadir)
        $this->assertEquals('green', $markersByStatus['PT Solusi Digital Prima']['status']);
        $this->assertEquals('Aman & Tertib', $markersByStatus['PT Solusi Digital Prima']['status_label']);

        // PT Kreasi Media Mandiri -> Yellow (Terlambat)
        $this->assertEquals('yellow', $markersByStatus['PT Kreasi Media Mandiri']['status']);
        $this->assertEquals('Perlu Perhatian', $markersByStatus['PT Kreasi Media Mandiri']['status_label']);

        // PT Industri Presisi Jawa -> Red (Alfa)
        $this->assertEquals('red', $markersByStatus['PT Industri Presisi Jawa']['status']);
        $this->assertEquals('Kritis / Ada Kendala', $markersByStatus['PT Industri Presisi Jawa']['status_label']);
    }

    public function test_company_turns_red_when_open_sos_incident_exists(): void
    {
        // Even if student 1 was present, an open SOS incident marks the company as red
        IncidentReport::create([
            'school_id' => $this->schoolA->id,
            'student_pkl_id' => $this->spkl1->id,
            'category' => 'emergency',
            'urgency' => 'critical',
            'subject' => 'Terjadi kecelakaan kerja di area workshop',
            'description' => 'Tangan terjepit mesin konveyor.',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->adminA)
            ->get(route('adminsekolah.map'));

        $response->assertStatus(200);
        $mapData = $response->viewData('mapData');
        $marker = collect($mapData['markers'])->firstWhere('name', 'PT Solusi Digital Prima');

        $this->assertEquals('red', $marker['status']);
        $this->assertTrue($marker['has_incident']);
        $this->assertStringContainsString('Kendala/SOS', $marker['status_detail']);
    }

    public function test_guru_pembimbing_can_view_map_for_supervised_students(): void
    {
        $response = $this->actingAs($this->teacherAUser)
            ->get(route('guru.map', ['scope' => 'bimbingan']));

        $response->assertStatus(200);
        $response->assertSee('Peta Sebaran Siswa Bimbingan PKL');
        $response->assertSee('PT Solusi Digital Prima');
    }

    public function test_kepala_sekolah_can_view_executive_gis_map(): void
    {
        $response = $this->actingAs($this->kepsekA)
            ->get(route('kepsek.map'));

        $response->assertStatus(200);
        $response->assertSee('Peta Sebaran PKL & Monitoring Wilayah');
        $response->assertSee('PT Solusi Digital Prima');
    }

    public function test_filter_by_status_color_works_properly(): void
    {
        // Filter only RED status markers
        $response = $this->actingAs($this->adminA)
            ->get(route('adminsekolah.map', ['status_filter' => 'red']));

        $response->assertStatus(200);
        $mapData = $response->viewData('mapData');

        $this->assertCount(1, $mapData['markers']);
        $this->assertEquals('PT Industri Presisi Jawa', $mapData['markers'][0]['name']);
    }

    public function test_multi_tenant_isolation_prevents_viewing_other_school_companies(): void
    {
        // Company in School B
        Company::create([
            'school_id' => $this->schoolB->id,
            'name' => 'PT Rahasia Cirebon',
            'address' => 'Jl. Cirebon No. 99',
            'contact_person' => 'Rahasia HRD',
            'phone' => '0231-999999',
            'latitude' => -6.7200,
            'longitude' => 108.5500,
            'radius_meters' => 100,
        ]);

        $response = $this->actingAs($this->adminA)
            ->get(route('adminsekolah.map'));

        $response->assertStatus(200);
        $response->assertDontSee('PT Rahasia Cirebon');

        $mapData = $response->viewData('mapData');
        $companyNames = collect($mapData['markers'])->pluck('name')->all();
        $this->assertNotContains('PT Rahasia Cirebon', $companyNames);
    }
}
