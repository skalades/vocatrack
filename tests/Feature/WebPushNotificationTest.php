<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AttendanceRequest;
use App\Models\Company;
use App\Models\IncidentFollowup;
use App\Models\IncidentReport;
use App\Models\Journal;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use App\Notifications\IncidentFollowupNotification;
use App\Notifications\JournalStatusUpdatedNotification;
use App\Notifications\RequestStatusUpdatedNotification;
use App\Services\AnnouncementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class WebPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private Major $rpl;
    private Major $dkv;
    private User $teacherUser;
    private User $studentUser;
    private User $otherStudentUser;
    private StudentPkl $spkl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '20208888',
            'name' => 'SMK Negeri 2 Garut',
            'email' => 'info@smkn2garut.sch.id',
            'address' => 'Garut',
            'phone' => '0262',
            'is_active' => true,
        ]);

        $this->rpl = Major::create(['school_id' => $this->school->id, 'name' => 'Rekayasa Perangkat Lunak', 'code' => 'RPL']);
        $this->dkv = Major::create(['school_id' => $this->school->id, 'name' => 'Desain Komunikasi Visual', 'code' => 'DKV']);

        $classRpl = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->rpl->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $classDkv = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->dkv->id,
            'name' => 'XII DKV 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing',
            'email' => 'guru@smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $this->teacherUser->id, 'nip' => '19890101']);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa RPL',
            'email' => 'siswa.rpl@smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create(['user_id' => $this->studentUser->id, 'class_id' => $classRpl->id, 'nisn' => '0051111111', 'nis' => '1111']);

        $this->otherStudentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa DKV',
            'email' => 'siswa.dkv@smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Student::create(['user_id' => $this->otherStudentUser->id, 'class_id' => $classDkv->id, 'nisn' => '0052222222', 'nis' => '2222']);

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
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);
    }

    public function test_authenticated_student_can_subscribe_and_unsubscribe_push_endpoint(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/sample-android-endpoint-token';

        $this->actingAs($this->studentUser)
            ->postJson(route('push-subscriptions.store'), [
                'endpoint' => $endpoint,
                'keys' => [
                    'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8QcYP7DkM',
                    'auth' => 'tBHItJI5svbpez7KI4CCXg',
                ],
                'content_encoding' => 'aes128gcm',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'subscribed' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_type' => User::class,
            'subscribable_id' => $this->studentUser->id,
            'endpoint' => $endpoint,
            'content_encoding' => 'aes128gcm',
        ]);

        // Jika perangkat dipakai akun lain, endpoint berpindah kepemilikan ke user baru.
        $this->actingAs($this->otherStudentUser)
            ->postJson(route('push-subscriptions.store'), [
                'endpoint' => $endpoint,
                'keys' => [
                    'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8QcYP7DkM',
                    'auth' => 'tBHItJI5svbpez7KI4CCXg',
                ],
                'content_encoding' => 'aes128gcm',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', [
            'subscribable_id' => $this->studentUser->id,
            'endpoint' => $endpoint,
        ]);
        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $this->otherStudentUser->id,
            'endpoint' => $endpoint,
        ]);

        $this->actingAs($this->otherStudentUser)
            ->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => $endpoint])
            ->assertOk()
            ->assertJson(['ok' => true, 'subscribed' => false]);

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $endpoint]);
    }

    public function test_notifications_use_webpush_channel_only_when_vapid_and_subscription_exist(): void
    {
        $journal = Journal::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => Carbon::today()->toDateString(),
            'activity_description' => 'Membuat modul autentikasi Laravel.',
            'status' => 'approved',
        ]);

        $attRequest = AttendanceRequest::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'type' => 'izin',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'reason' => 'Keperluan keluarga',
            'status' => 'approved',
        ]);

        $report = IncidentReport::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'category' => 'safety_health',
            'severity' => 'medium',
            'subject' => 'Kendala peralatan',
            'description' => 'Butuh arahan alat kerja.',
            'status' => 'in_progress',
        ]);
        $followup = IncidentFollowup::create([
            'incident_report_id' => $report->id,
            'user_id' => $this->teacherUser->id,
            'action_note' => 'Sudah dikoordinasikan dengan mentor.',
        ]);

        $journalNotif = new JournalStatusUpdatedNotification($journal, 'approved');
        $requestNotif = new RequestStatusUpdatedNotification($attRequest, 'approved');
        $incidentNotif = new IncidentFollowupNotification($report, $followup, 'in_progress');

        // Tanpa subscription & VAPID, WebPushChannel tidak dipakai.
        $this->assertNotContains(WebPushChannel::class, $journalNotif->via($this->studentUser));

        // Dengan VAPID + subscription, WebPushChannel aktif dan payload valid.
        config()->set('webpush.vapid.public_key', 'BTestPublicKey123');
        config()->set('webpush.vapid.private_key', 'TestPrivateKey123');
        $this->studentUser->updatePushSubscription(
            'https://web.push.apple.com/QSampleIosEndpoint',
            'p256dh-key',
            'auth-key',
            'aes128gcm'
        );

        $this->assertContains(WebPushChannel::class, $journalNotif->via($this->studentUser));
        $this->assertContains(WebPushChannel::class, $requestNotif->via($this->studentUser));
        $this->assertContains(WebPushChannel::class, $incidentNotif->via($this->studentUser));

        $pushArray = $journalNotif->toWebPush($this->studentUser, $journalNotif)->toArray();
        $expectedLogo = \App\Models\PlatformSetting::getPwaLogoPath();
        $this->assertSame($expectedLogo, $pushArray['icon']);
        $this->assertSame($expectedLogo, $pushArray['badge']);
        $this->assertSame('/siswa/jurnal', $pushArray['data']['url']);

        // Saat Super Admin mengunggah logo sistem kustom, ikon Web Push & manifest mengikuti logo sistem saja
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Http\UploadedFile::fake()->image('system_custom.png', 512, 512)->storeAs('branding', 'system_custom.png', 'public');
        \App\Models\PlatformSetting::set('system_logo', 'branding/system_custom.png');
        $this->artisan('vokatrack:sync-pwa')->assertSuccessful();

        $customPush = $journalNotif->toWebPush($this->studentUser, $journalNotif)->toArray();
        $this->assertStringContainsString('system_custom.png', $customPush['icon']);

        $teacherManifest = $this->get('/manifest-guru.webmanifest')->assertOk()->json();
        $this->assertCount(3, $teacherManifest['icons']);
        $this->assertStringContainsString('system_custom.png', $teacherManifest['icons'][0]['src']);

        \App\Models\PlatformSetting::forget('system_logo');
        $this->artisan('vokatrack:sync-pwa')->assertSuccessful();
    }

    public function test_announcement_creation_notifies_only_targeted_students(): void
    {
        Notification::fake();

        $announcement = app(AnnouncementService::class)->createAnnouncement($this->teacherUser, [
            'title' => 'Pengumpulan Laporan RPL',
            'content' => 'Harap unggah jurnal mingguan sebelum Jumat pukul 16.00 WIB.',
            'category' => 'penting',
            'target_role' => 'siswa',
            'target_major_id' => $this->rpl->id,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Announcement::class, $announcement);

        Notification::assertSentTo($this->studentUser, AnnouncementPublishedNotification::class);
        Notification::assertNotSentTo($this->otherStudentUser, AnnouncementPublishedNotification::class);
        Notification::assertNotSentTo($this->teacherUser, AnnouncementPublishedNotification::class);
    }

    public function test_pwa_service_worker_and_student_home_expose_push_hooks(): void
    {
        $sw = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString("addEventListener('push'", $sw);
        $this->assertStringContainsString("addEventListener('notificationclick'", $sw);

        config()->set('webpush.vapid.public_key', 'BTestVapidPublicKeyForBlade');

        $html = $this->actingAs($this->studentUser)
            ->get(route('student.home'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-vapid-public-key="BTestVapidPublicKeyForBlade"', $html);
        $this->assertStringContainsString('id="pwa-push-section"', $html);
    }
}
