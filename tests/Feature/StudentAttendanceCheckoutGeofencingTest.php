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
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentAttendanceCheckoutGeofencingTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected Company $company;
    protected StudentPkl $spkl;
    protected User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->school = School::create([
            'npsn' => '66667777',
            'name' => 'SMK Checkout Test',
            'address' => 'Bandung',
            'phone' => '022-666',
            'email' => 'smk@checkout.sch.id',
            'is_active' => true,
        ]);

        $major = Major::create(['school_id' => $this->school->id, 'code' => 'RPL', 'name' => 'RPL']);
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $this->company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Geofence Checkout',
            'address' => 'Jl. Merdeka No. 10',
            'contact_person' => 'Budi PIC',
            'phone' => '0812345678',
            'work_days' => '5_days',
            'gps_enabled' => true,
            'latitude' => -6.914744,
            'longitude' => 107.609810,
            'radius_meters' => 150,
            'is_pkl_started' => true,
            'override_hours' => true,
            'custom_work_hours' => json_encode([
                'checkin_start' => '06:00:00',
                'checkin_late' => '08:00:00',
                'checkin_alfa' => '11:00:00',
                'checkout_start' => '16:00:00',
            ]),
        ]);

        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Periode 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $guruUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing',
            'email' => 'guru@checkout.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $guruUser->id, 'nip' => '123456789']);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Checkout',
            'email' => 'siswa@checkout.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_id' => $class->id,
            'nis' => '1001',
            'nisn' => '0011223344',
        ]);

        $this->spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $this->company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
    }

    public function test_checkout_requires_coordinates_when_gps_enabled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 16:30:00'));

        // Check-in dulu di pagi hari
        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-11-18',
            'checkin_time' => '07:30:00',
            'status' => 'hadir',
            'is_within_radius' => true,
        ]);

        $response = $this->actingAs($this->studentUser)->post(route('student.checkout'), [
            'checkout_notes' => 'Selesai tugas',
        ]);

        $response->assertSessionHasErrors(['location']);
    }

    public function test_checkout_outside_radius_requires_reason(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 16:45:00'));

        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-11-18',
            'checkin_time' => '07:30:00',
            'status' => 'hadir',
            'is_within_radius' => true,
        ]);

        // Koordinat jauh di luar kantor (Jakarta vs Bandung)
        $farLat = -6.2088;
        $farLng = 106.8456;

        // Tanpa alasan -> gagal validasi
        $response1 = $this->actingAs($this->studentUser)->post(route('student.checkout'), [
            'latitude' => $farLat,
            'longitude' => $farLng,
            'accuracy' => 15,
            'is_mocked' => false,
            'checkout_notes' => 'Pulang dari lokasi klien',
        ]);
        $response1->assertSessionHasErrors(['reason']);

        // Dengan alasan valid -> berhasil
        $response2 = $this->actingAs($this->studentUser)->post(route('student.checkout'), [
            'latitude' => $farLat,
            'longitude' => $farLng,
            'accuracy' => 15,
            'is_mocked' => false,
            'reason' => 'Selesai instalasi jaringan di kantor cabang klien',
            'checkout_notes' => 'Pekerjaan selesai 100%',
        ]);
        $response2->assertRedirect(route('student.attendance'));
        $response2->assertSessionHas('success');

        $attendance = Attendance::where('student_pkl_id', $this->spkl->id)->whereDate('date', '2026-11-18')->first();
        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->checkout_time);
        $this->assertFalse((bool) $attendance->is_checkout_within_radius);
        $this->assertStringContainsString('Selesai instalasi jaringan', $attendance->checkout_notes);
    }

    public function test_checkout_with_selfie_saves_photo_to_private_disk_and_sets_geofence_attributes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 17:00:00'));

        $attendance = Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-11-18',
            'checkin_time' => '07:30:00',
            'status' => 'hadir',
            'is_within_radius' => true,
        ]);

        $base64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($this->studentUser)->post(route('student.checkout'), [
            'latitude' => -6.914740,
            'longitude' => 107.609815,
            'accuracy' => 10,
            'is_mocked' => false,
            'photo' => $base64Png,
            'checkout_notes' => 'Membersihkan meja kerja & merapikan berkas',
        ]);

        $response->assertRedirect(route('student.attendance'));
        $response->assertSessionHas('success');

        $attendance->refresh();
        $this->assertEquals('17:00:00', $attendance->checkout_time);
        $this->assertNotNull($attendance->checkout_photo_path);
        $this->assertTrue((bool) $attendance->is_checkout_within_radius);
        $this->assertEquals(10, $attendance->checkout_accuracy_meters);
        $this->assertFalse((bool) $attendance->is_checkout_mock_detected);

        Storage::disk('local')->assertExists($attendance->checkout_photo_path);
    }

    public function test_checkout_before_start_time_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 14:00:00')); // Sebelum jam 16:00

        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-11-18',
            'checkin_time' => '07:30:00',
            'status' => 'hadir',
            'is_within_radius' => true,
        ]);

        $response = $this->actingAs($this->studentUser)->post(route('student.checkout'), [
            'latitude' => -6.914740,
            'longitude' => 107.609815,
            'accuracy' => 10,
            'is_mocked' => false,
        ]);

        $response->assertSessionHasErrors(['checkout']);
    }

    public function test_checkout_blocked_when_pkl_not_started(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 16:30:00'));

        $this->company->update(['is_pkl_started' => false]);

        $response = $this->actingAs($this->studentUser)->post(route('student.checkout'), [
            'latitude' => -6.914740,
            'longitude' => 107.609815,
            'accuracy' => 10,
            'is_mocked' => false,
        ]);

        $response->assertSessionHasErrors(['checkout']);
    }

    public function test_checkout_without_selfie_succeeds_when_school_disables_selfie(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 16:30:00'));

        \App\Models\SchoolSetting::create([
            'school_id' => $this->school->id,
            'key' => 'feature_checkout_selfie',
            'value' => '0',
        ]);
        \Illuminate\Support\Facades\Cache::forget(\App\Models\SchoolSetting::cacheKey((int) $this->school->id));

        $attendance = Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-11-18',
            'checkin_time' => '07:30:00',
            'status' => 'hadir',
            'is_within_radius' => true,
        ]);

        $response = $this->actingAs($this->studentUser)->post(route('student.checkout'), [
            'latitude' => -6.914740,
            'longitude' => 107.609815,
            'accuracy' => 8,
            'is_mocked' => false,
            'photo' => null,
            'checkout_notes' => 'Selesai shift kerja sore tanpa selfie',
        ]);

        $response->assertRedirect(route('student.attendance'));
        $response->assertSessionHas('success');

        $attendance->refresh();
        $this->assertEquals('16:30:00', $attendance->checkout_time);
        $this->assertNull($attendance->checkout_photo_path);
        $this->assertTrue((bool) $attendance->is_checkout_within_radius);
        $this->assertStringContainsString('Selesai shift kerja sore tanpa selfie', (string) $attendance->checkout_notes);
    }

    public function test_checkout_view_renders_no_selfie_ui_when_school_disables_checkout_selfie(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 16:30:00'));

        \App\Models\SchoolSetting::create([
            'school_id' => $this->school->id,
            'key' => 'feature_checkout_selfie',
            'value' => '0',
        ]);
        \Illuminate\Support\Facades\Cache::forget(\App\Models\SchoolSetting::cacheKey((int) $this->school->id));

        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-11-18',
            'checkin_time' => '07:30:00',
            'status' => 'hadir',
            'is_within_radius' => true,
        ]);

        $response = $this->actingAs($this->studentUser)->get(route('student.attendance', ['mode' => 'checkout']));

        $response->assertOk();
        $response->assertSee('Presensi Cepat · Bebas Kamera');
        $response->assertSee('Presensi Pulang Mandiri');
        $response->assertSee('Sekolah menonaktifkan kewajiban swafoto');
        $response->assertDontSee('id="webcam-feed"', false);
        $response->assertDontSee('id="snapshot-canvas"', false);
    }
}
