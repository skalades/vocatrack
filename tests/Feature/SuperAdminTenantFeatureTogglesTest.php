<?php

namespace Tests\Feature;

use App\Channels\WhatsAppChannel;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use App\Services\SecureFileService;
use App\Services\WhatsAppGatewayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DummyWhatsAppNotification extends Notification
{
    public function getNotificationType(): string
    {
        return 'test_alert';
    }

    public function toWhatsApp(object $notifiable): string
    {
        return 'Halo, ini uji coba pesan WhatsApp.';
    }
}

class SuperAdminTenantFeatureTogglesTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $superAdmin;
    protected User $studentUser;
    protected Student $student;
    protected Company $company;
    protected StudentPkl $spkl;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(SecureFileService::DISK);
        WhatsAppGatewayService::fake();

        $this->superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@vokatrack.test',
            'password' => bcrypt('password'),
            'role' => 'super-admin',
            'is_active' => true,
        ]);

        $this->school = School::create([
            'name' => 'SMK Negeri 2 Garut',
            'npsn' => '20209203',
            'email' => 'info@smkn2garut.sch.id',
            'address' => 'Jl. Suherman No. 90',
            'phone' => '0262-233131',
            'is_active' => true,
        ]);

        $major = Major::create([
            'school_id' => $this->school->id,
            'code' => 'GP',
            'name' => 'Teknik Geologi Pertambangan',
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII GP 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing',
            'email' => 'guru@smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'nip' => '19850101']);

        $this->company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Pertamina Geothermal',
            'address' => 'Kamojang, Garut',
            'contact_person' => 'Wahyu PIC',
            'phone' => '081234567890',
            'work_days' => '5_days',
            'gps_enabled' => true,
            'latitude' => -7.1458,
            'longitude' => 107.7986,
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

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Carrel Munjid Al Rasyid',
            'email' => 'carrel@siswa.smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_id' => $class->id,
            'nis' => '12001',
            'nisn' => '0081234567',
        ]);

        $this->spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $this->company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
    }

    protected function tearDown(): void
    {
        WhatsAppGatewayService::resetFake();
        parent::tearDown();
    }

    public function test_superadmin_can_update_school_feature_toggles_and_creates_audit_log(): void
    {
        $response = $this->actingAs($this->superAdmin)->put(route('superadmin.schools.update-features', $this->school), [
            'feature_checkin_selfie' => '0',
            'feature_checkin_geofence' => '0',
            'feature_checkout_selfie' => '0',
            'feature_checkout_geofence' => '0',
            'feature_whatsapp' => '0',
            'feature_journal_photo' => '1',
            'feature_parent_portal' => '0',
        ]);

        $response->assertRedirect(route('superadmin.schools.show', ['school' => $this->school->id, 'tab' => 'pengaturan']));
        $response->assertSessionHas('success');

        $this->assertFalse($this->school->isFeatureEnabled('feature_checkin_selfie'));
        $this->assertFalse($this->school->isFeatureEnabled('feature_checkin_geofence'));
        $this->assertFalse($this->school->isFeatureEnabled('feature_checkout_selfie'));
        $this->assertFalse($this->school->isFeatureEnabled('feature_checkout_geofence'));
        $this->assertFalse($this->school->isFeatureEnabled('feature_whatsapp'));
        $this->assertTrue($this->school->isFeatureEnabled('feature_journal_photo'));
        $this->assertFalse($this->school->isFeatureEnabled('feature_parent_portal'));

        $this->assertDatabaseHas('activity_logs', [
            'school_id' => $this->school->id,
            'action' => 'school_features_updated',
        ]);
    }

    public function test_checkin_without_photo_succeeds_when_checkin_selfie_is_disabled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 07:15:00'));

        // Disable checkin selfie for this tenant
        SchoolSetting::updateOrCreate(
            ['school_id' => $this->school->id, 'key' => 'feature_checkin_selfie'],
            ['value' => '0']
        );

        $response = $this->actingAs($this->studentUser)->post(route('student.checkin'), [
            'latitude' => -7.1458,
            'longitude' => 107.7986,
            'accuracy' => 10,
            // no photo submitted
        ]);

        $response->assertSessionHas('success');

        $attendance = Attendance::where('student_pkl_id', $this->spkl->id)->whereDate('date', '2026-11-18')->first();
        $this->assertNotNull($attendance);
        $this->assertNull($attendance->checkin_photo_path);
        $this->assertEquals('hadir', $attendance->status);
    }

    public function test_checkin_outside_radius_succeeds_without_reason_when_geofence_is_disabled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 07:20:00'));

        // Disable checkin geofencing for this tenant
        SchoolSetting::updateOrCreate(
            ['school_id' => $this->school->id, 'key' => 'feature_checkin_geofence'],
            ['value' => '0']
        );

        // Coordinates far away (Jakarta coordinates vs Kamojang Garut)
        $farLat = -6.2088;
        $farLng = 106.8456;

        $response = $this->actingAs($this->studentUser)->post(route('student.checkin'), [
            'latitude' => $farLat,
            'longitude' => $farLng,
            'accuracy' => 15,
            // no reason provided
        ]);

        $response->assertSessionHas('success');

        $attendance = Attendance::where('student_pkl_id', $this->spkl->id)->whereDate('date', '2026-11-18')->first();
        $this->assertNotNull($attendance);
        $this->assertTrue((bool) $attendance->is_within_radius);
    }

    public function test_checkout_without_photo_and_outside_radius_succeeds_when_features_disabled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 16:30:00'));

        // Checkin in the morning
        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => '2026-11-18',
            'checkin_time' => '07:30:00',
            'status' => 'hadir',
            'is_within_radius' => true,
        ]);

        // Disable checkout selfie and geofence
        SchoolSetting::updateOrCreate(
            ['school_id' => $this->school->id, 'key' => 'feature_checkout_selfie'],
            ['value' => '0']
        );
        SchoolSetting::updateOrCreate(
            ['school_id' => $this->school->id, 'key' => 'feature_checkout_geofence'],
            ['value' => '0']
        );

        $farLat = -6.2088;
        $farLng = 106.8456;

        $response = $this->actingAs($this->studentUser)->post(route('student.checkout'), [
            'latitude' => $farLat,
            'longitude' => $farLng,
            'accuracy' => 15,
            'checkout_notes' => 'Pulang kantor tanpa kamera dan bebas radius',
        ]);

        $response->assertRedirect(route('student.attendance'));
        $response->assertSessionHas('success');

        $attendance = Attendance::where('student_pkl_id', $this->spkl->id)->whereDate('date', '2026-11-18')->first();
        $this->assertNotNull($attendance->checkout_time);
        $this->assertNull($attendance->checkout_photo_path);
        $this->assertTrue((bool) $attendance->is_checkout_within_radius);
    }

    public function test_whatsapp_notifications_are_skipped_when_tenant_whatsapp_is_disabled(): void
    {
        // Set user valid phone number
        $this->studentUser->update(['wa_number' => '081234567890']);

        // Disable WhatsApp for tenant
        SchoolSetting::updateOrCreate(
            ['school_id' => $this->school->id, 'key' => 'feature_whatsapp'],
            ['value' => '0']
        );

        $channel = app(WhatsAppChannel::class);
        $notification = new DummyWhatsAppNotification();

        $channel->send($this->studentUser, $notification);

        // Asserts WhatsAppGatewayService did NOT send any message
        $this->assertEmpty(WhatsAppGatewayService::getSentMessages());
    }

    public function test_parent_portal_is_blocked_when_feature_parent_portal_is_disabled(): void
    {
        // Disable parent portal for this school
        SchoolSetting::updateOrCreate(
            ['school_id' => $this->school->id, 'key' => 'feature_parent_portal'],
            ['value' => '0']
        );

        $token = $this->student->parent_token;
        $this->assertNotEmpty($token);

        $response = $this->get(route('parent.monitor', ['token' => $token]));
        $response->assertStatus(403);
        $response->assertSee('Akses portal monitoring orang tua sedang dinonaktifkan oleh kebijakan sekolah');
    }
}
