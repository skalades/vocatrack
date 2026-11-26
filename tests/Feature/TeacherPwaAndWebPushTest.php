<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPasswordResetRequest;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\EmergencyIncidentReportedNotification;
use App\Notifications\StudentPasswordResetRequestedNotification;
use App\Notifications\TestWebPushNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class TeacherPwaAndWebPushTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $teacherUser;
    private Student $student;
    private StudentPkl $spkl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '20207777',
            'name' => 'SMK Negeri 2 Garut',
            'email' => 'info@smkn2garut-guru.sch.id',
            'address' => 'Garut',
            'phone' => '0262',
            'is_active' => true,
        ]);

        $major = Major::create(['school_id' => $this->school->id, 'name' => 'RPL', 'code' => 'RPL']);
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Herdi Permana',
            'email' => 'herdi.guru@smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $this->teacherUser->id, 'nip' => '19880101']);

        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Binaan',
            'email' => 'siswa.binaan@smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'nisn' => '0053333333',
            'nis' => '3333',
        ]);

        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Mitra Garut',
            'address' => 'Garut',
            'contact_person' => 'PIC',
            'phone' => '0811',
            'work_days' => '5_days',
            'latitude' => -7.2,
            'longitude' => 107.9,
            'radius_meters' => 100,
            'is_pkl_started' => true,
        ]);

        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Periode 2026',
            'start_date' => Carbon::today()->subMonth()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        $this->spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);
    }

    public function test_teacher_manifest_route_and_sync_are_valid(): void
    {
        $this->artisan('vokatrack:sync-pwa')->assertSuccessful();
        $this->assertFileExists(public_path('manifest-guru.webmanifest'));

        $response = $this->get('/manifest-guru.webmanifest')->assertOk();
        $this->assertStringContainsString('application/manifest+json', (string) $response->headers->get('Content-Type'));

        $data = $response->json();
        $this->assertSame('/guru/dashboard', $data['id']);
        $this->assertSame('/guru/dashboard?source=pwa', $data['start_url']);
        $this->assertSame('/', $data['scope']);
        $this->assertStringContainsString('Guru', $data['short_name']);
    }

    public function test_teacher_mobile_and_desktop_dashboards_expose_pwa_and_push_hooks(): void
    {
        config()->set('webpush.vapid.public_key', 'BTeacherVapidKey');

        // Mobile view
        $mobileHtml = $this->actingAs($this->teacherUser)
            ->get(route('guru.dashboard', ['view' => 'mobile']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('/manifest-guru.webmanifest', $mobileHtml);
        $this->assertStringContainsString('data-vapid-public-key="BTeacherVapidKey"', $mobileHtml);
        $this->assertStringContainsString('id="teacher-pwa-card"', $mobileHtml);
        $this->assertStringContainsString('id="pwa-push-section"', $mobileHtml);
        $this->assertStringNotContainsString('&amp;amp;', $mobileHtml);

        // Desktop view
        $desktopHtml = $this->actingAs($this->teacherUser)
            ->get(route('guru.dashboard', ['view' => 'desktop']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('/manifest-guru.webmanifest', $desktopHtml);
        $this->assertStringContainsString('data-vapid-public-key="BTeacherVapidKey"', $desktopHtml);
        $this->assertStringContainsString('id="teacher-pwa-card"', $desktopHtml);
    }

    public function test_teacher_notifications_include_webpush_channel_when_subscribed(): void
    {
        config()->set('webpush.vapid.public_key', 'BTestPublicKey123');
        config()->set('webpush.vapid.private_key', 'TestPrivateKey123');

        $this->teacherUser->updatePushSubscription(
            'https://fcm.googleapis.com/fcm/send/teacher-device-token',
            'p256dh-teacher',
            'auth-teacher',
            'aes128gcm'
        );

        $incident = IncidentReport::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'category' => 'emergency',
            'severity' => 'critical',
            'subject' => 'Butuh Bantuan Mendesak',
            'description' => 'Kendala darurat di lokasi PKL.',
            'status' => 'submitted',
        ]);

        $sosNotif = new EmergencyIncidentReportedNotification($incident);
        $this->assertContains(WebPushChannel::class, $sosNotif->via($this->teacherUser));
        $sosPayload = $sosNotif->toWebPush($this->teacherUser, $sosNotif)->toArray();
        $this->assertStringContainsString('/guru/incidents?incident_id=' . $incident->id, $sosPayload['data']['url']);
        $this->assertTrue($sosPayload['requireInteraction']);

        $resetRequest = StudentPasswordResetRequest::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'email' => 'siswa.binaan@smkn2garut.sch.id',
            'status' => 'pending',
        ]);

        $resetNotif = new StudentPasswordResetRequestedNotification($this->student, $resetRequest);
        $this->assertContains(WebPushChannel::class, $resetNotif->via($this->teacherUser));
        $resetPayload = $resetNotif->toWebPush($this->teacherUser, $resetNotif)->toArray();
        $this->assertStringContainsString('/guru/monitoring?q=0053333333', $resetPayload['data']['url']);

        $testNotif = new TestWebPushNotification();
        $testPayload = $testNotif->toWebPush($this->teacherUser, $testNotif)->toArray();
        $this->assertSame('/guru/dashboard', $testPayload['data']['url']);
    }
}
