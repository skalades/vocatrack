<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Journal;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSekolahTestModeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminSekolah;
    protected Company $unstartedCompany;
    protected Company $startedCompany;
    protected StudentPkl $spklTest;
    protected StudentPkl $spklReal;
    protected User $studentUserTest;
    protected User $studentUserReal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        Storage::fake('public');

        $this->school = School::first();
        $this->adminSekolah = User::where('role', 'admin-sekolah')
            ->where('school_id', $this->school->id)
            ->first();

        // 1. DU/DI belum mulai PKL (untuk siswa uji coba)
        $this->unstartedCompany = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Uji Coba Simulasi',
            'address' => 'Jl. Uji Coba No. 1',
            'contact_person' => 'Tester',
            'phone' => '081233334444',
            'latitude' => -6.9,
            'longitude' => 107.8,
            'radius_meters' => 50,
            'gps_enabled' => true,
            'is_pkl_started' => false,
            'work_days' => '5_days',
        ]);

        // 2. DU/DI sudah mulai PKL (untuk siswa aktif PKL)
        $this->startedCompany = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Industri Resmi',
            'address' => 'Jl. Industri Resmi No. 2',
            'contact_person' => 'Pembimbing Resmi',
            'phone' => '081255556666',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius_meters' => 50,
            'gps_enabled' => true,
            'is_pkl_started' => true,
            'work_days' => '5_days',
        ]);

        // Ambil 2 user siswa
        $studentUsers = User::where('role', 'siswa')
            ->where('school_id', $this->school->id)
            ->with(['student', 'profile', 'school'])
            ->take(2)
            ->get();

        $this->studentUserTest = $studentUsers[0];
        $this->studentUserReal = $studentUsers[1];

        // Buat atau update student_pkl
        $this->spklTest = StudentPkl::where('student_id', $this->studentUserTest->student->id)->first();
        $this->spklTest->update([
            'company_id' => $this->unstartedCompany->id,
            'status' => 'active',
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addMonths(3),
        ]);

        $this->spklReal = StudentPkl::where('student_id', $this->studentUserReal->student->id)->first();
        $this->spklReal->update([
            'company_id' => $this->startedCompany->id,
            'status' => 'active',
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addMonths(3),
        ]);
    }

    public function test_admin_sekolah_can_view_settings_page_with_test_mode_panel(): void
    {
        $response = $this->actingAs($this->adminSekolah)->get(route('adminsekolah.settings'));
        $response->assertStatus(200);
        $response->assertSee('Mode Simulasi & Uji Coba Presensi');
        $response->assertSee('Pembersihan Data Uji Coba');
    }

    public function test_admin_sekolah_can_toggle_test_mode_on_and_off(): void
    {
        $this->assertFalse($this->school->fresh()->isTestModeActive());

        // 1. Toggle ON
        $response = $this->actingAs($this->adminSekolah)
            ->post(route('adminsekolah.test-mode.toggle'));
        $response->assertSessionHas('success');
        $this->assertTrue($this->school->fresh()->isTestModeActive());

        // 2. Toggle OFF
        $response = $this->actingAs($this->adminSekolah)
            ->post(route('adminsekolah.test-mode.toggle'));
        $response->assertSessionHas('success');
        $this->assertFalse($this->school->fresh()->isTestModeActive());
    }

    public function test_when_test_mode_active_unstarted_student_can_checkin_outside_radius_with_simulasi_tag(): void
    {
        // Aktifkan mode simulasi
        $this->actingAs($this->adminSekolah)->post(route('adminsekolah.test-mode.toggle'));
        $this->assertTrue($this->school->fresh()->isTestModeActive());
        $this->assertTrue($this->spklTest->fresh()->isTestStudent());

        // Siswa uji coba presensi di lokasi yang SANGAT JAUH dari kantor (misal 50 km)
        $response = $this->actingAs($this->studentUserTest)->postJson(route('student.checkin'), [
            'latitude' => -7.5,
            'longitude' => 108.5,
            'accuracy' => 15,
            'is_mocked' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verifikasi catatan kehadiran mengandung [SIMULASI]
        $attendance = Attendance::where('student_pkl_id', $this->spklTest->id)
            ->forDate(Carbon::today()->toDateString())
            ->first();

        $this->assertNotNull($attendance);
        $this->assertTrue((bool) $attendance->is_within_radius);
        $this->assertStringContainsString('[SIMULASI]', $attendance->checkout_notes);
    }

    public function test_when_test_mode_active_real_student_outside_radius_is_still_strictly_rejected(): void
    {
        // Aktifkan mode simulasi
        $this->actingAs($this->adminSekolah)->post(route('adminsekolah.test-mode.toggle'));
        $this->assertTrue($this->school->fresh()->isTestModeActive());

        // Siswa nyata PKL BUKAN test student
        $this->assertFalse($this->spklReal->fresh()->isTestStudent());

        // Siswa nyata coba checkin di luar radius kantor tanpa alasan valid
        $response = $this->actingAs($this->studentUserReal)->postJson(route('student.checkin'), [
            'latitude' => -7.5, // Jauh dari -6.2
            'longitude' => 108.5,
            'accuracy' => 15,
            'is_mocked' => false,
        ]);

        // HARUS DITOLAK karena siswa asli tetap wajib geofencing!
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);

        $attendance = Attendance::where('student_pkl_id', $this->spklReal->id)
            ->forDate(Carbon::today()->toDateString())
            ->first();
        $this->assertNull($attendance, 'Siswa resmi tidak boleh presensi di luar radius kantor!');
    }

    public function test_admin_sekolah_can_fetch_stats_and_clean_test_data_batch(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Buat presensi uji coba
        $testSelfie = 'presensi/test_selfie.jpg';
        Storage::disk('local')->put($testSelfie, 'content test');

        $attTest = Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklTest->id,
            'date' => $today,
            'status' => 'hadir',
            'checkin_time' => '08:00:00',
            'checkin_photo_path' => $testSelfie,
            'checkout_notes' => '[SIMULASI]',
        ]);

        // 2. Buat presensi siswa asli PKL
        $realSelfie = 'presensi/real_selfie.jpg';
        Storage::disk('local')->put($realSelfie, 'content real');

        $attReal = Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklReal->id,
            'date' => $today,
            'status' => 'hadir',
            'checkin_time' => '07:15:00',
            'checkin_photo_path' => $realSelfie,
        ]);

        // 3. Cek stats API
        $statsResponse = $this->actingAs($this->adminSekolah)
            ->getJson(route('adminsekolah.test-mode.stats', ['date' => $today]));
        $statsResponse->assertStatus(200);
        $statsResponse->assertJson([
            'attendances_count' => 1,
        ]);

        // 4. Eksekusi batch cleanup via web action
        $cleanResponse = $this->actingAs($this->adminSekolah)
            ->post(route('adminsekolah.test-mode.clean'), [
                'date' => $today,
            ]);

        $cleanResponse->assertSessionHas('success');

        // Data test TERHAPUS
        $this->assertDatabaseMissing('attendances', ['id' => $attTest->id]);
        Storage::disk('local')->assertMissing($testSelfie);

        // Data siswa resmi PKL 100% AMAN & TETAP ADA!
        $this->assertDatabaseHas('attendances', ['id' => $attReal->id]);
        Storage::disk('local')->assertExists($realSelfie);
    }
}
