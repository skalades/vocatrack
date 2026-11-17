<?php

namespace Tests\Feature;

use App\Channels\WhatsAppChannel;
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
use App\Notifications\EmergencyIncidentReportedNotification;
use App\Services\WhatsAppGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmergencySosMultiChannelTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected User $studentUser;
    protected Student $student;
    protected StudentPkl $studentPkl;

    protected function setUp(): void
    {
        parent::setUp();
        WhatsAppGatewayService::fake();

        $this->school = School::create([
            'npsn' => '99881122',
            'name' => 'SMK Multi Channel',
            'address' => 'Jl. Komunikasi No. 10',
            'phone' => '021-332211',
            'email' => 'info@smkmultichannel.sch.id',
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Ibu Guru Pembimbing',
            'email' => 'guru.sos@smkmultichannel.sch.id',
            'wa_number' => '081234567899',
            'password' => Hash::make('secret123'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
            'notification_preferences' => [
                'general' => ['mail' => true, 'whatsapp' => true],
                'emergency_incident' => ['mail' => false, 'whatsapp' => false], // Should be overridden by SOS policy!
            ],
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'nip' => '198505052010012002',
        ]);

        $major = Major::create([
            'school_id' => $this->school->id,
            'name' => 'Teknik Komputer dan Jaringan',
            'code' => 'TKJ',
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII TKJ 2',
            'academic_year' => '2026/2027',
        ]);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Bambang Siswa',
            'email' => 'bambang.sos@smkmultichannel.sch.id',
            'password' => Hash::make('secret123'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_id' => $class->id,
            'nis' => '54321',
            'nisn' => '0066123456',
        ]);

        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Mitra Telekomunikasi',
            'address' => 'Kawasan Industri Sentul',
            'contact_person' => 'Ibu Rina',
            'phone' => '081300001111',
        ]);

        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Periode 2026',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(2),
            'is_active' => true,
        ]);

        $this->studentPkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $this->teacher->id,
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        WhatsAppGatewayService::resetFake();
        parent::tearDown();
    }

    public function test_whatsapp_gateway_normalizes_indonesian_phone_numbers(): void
    {
        $this->assertSame('628123456789', WhatsAppGatewayService::normalizePhoneNumber('08123456789'));
        $this->assertSame('628123456789', WhatsAppGatewayService::normalizePhoneNumber('+628123456789'));
        $this->assertSame('628123456789', WhatsAppGatewayService::normalizePhoneNumber('628123456789'));
        $this->assertSame('628123456789', WhatsAppGatewayService::normalizePhoneNumber('0812-3456-789'));
        $this->assertTrue(WhatsAppGatewayService::isValidIndonesianNumber('08123456789'));
        $this->assertFalse(WhatsAppGatewayService::isValidIndonesianNumber('12345'));
    }

    public function test_emergency_incident_notification_has_multichannel_via_channels(): void
    {
        $report = IncidentReport::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->studentPkl->id,
            'reported_by_id' => $this->studentUser->id,
            'category' => 'emergency',
            'subject' => 'Kecelakaan saat instalasi tower di lokasi PKL',
            'description' => 'Terpeleset dan membutuhkan pertolongan medis segera.',
            'urgency' => 'critical',
            'status' => 'submitted',
        ]);

        $notification = new EmergencyIncidentReportedNotification($report);
        $channels = $notification->via($this->teacherUser);

        $this->assertContains('database', $channels);
        $this->assertContains('mail', $channels);
        $this->assertContains(WhatsAppChannel::class, $channels);

        $waMessage = $notification->toWhatsApp($this->teacherUser);
        $this->assertStringContainsString('CRITICAL', $waMessage);
        $this->assertStringContainsString('Bambang Siswa', $waMessage);
        $this->assertStringContainsString('PT Mitra Telekomunikasi', $waMessage);

        $mailMessage = $notification->toMail($this->teacherUser);
        $this->assertStringContainsString('Bambang Siswa', $mailMessage->subject);
    }

    public function test_user_cannot_opt_out_of_emergency_incident_sos(): void
    {
        // Meskipun di user preferences dimatikan, wantsChannel harus tetap TRUE untuk 'emergency_incident' dan 'sos'
        $this->assertTrue($this->teacherUser->wantsChannel('whatsapp', 'emergency_incident'));
        $this->assertTrue($this->teacherUser->wantsChannel('mail', 'emergency_incident'));
        $this->assertTrue($this->teacherUser->wantsChannel('whatsapp', 'sos'));
    }

    public function test_whatsapp_channel_delivers_message_to_gateway(): void
    {
        $report = IncidentReport::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->studentPkl->id,
            'reported_by_id' => $this->studentUser->id,
            'category' => 'emergency',
            'subject' => 'Pelecehan / intimidasi di lingkungan kantor',
            'description' => 'Siswa merasa tidak aman dan butuh penanganan guru pembimbing.',
            'urgency' => 'high',
            'status' => 'submitted',
        ]);

        $notification = new EmergencyIncidentReportedNotification($report);
        $gateway = app(WhatsAppGatewayService::class);
        $channel = new WhatsAppChannel($gateway);

        $channel->send($this->teacherUser, $notification);

        $sent = WhatsAppGatewayService::getSentMessages();
        $this->assertCount(1, $sent);
        $this->assertSame('6281234567899', $sent[0]['target']);
        $this->assertStringContainsString('HIGH', $sent[0]['message']);
        $this->assertStringContainsString('Bambang Siswa', $sent[0]['message']);
    }

    public function test_profile_contact_and_preferences_can_be_updated(): void
    {
        $response = $this->actingAs($this->teacherUser)->post(
            route('profile.contact-preferences'),
            [
                'wa_number' => '085299887766',
                'notification_preferences' => [
                    'journal_status' => ['mail' => true, 'whatsapp' => false],
                    'request_status' => ['mail' => true, 'whatsapp' => true],
                ],
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->teacherUser->refresh();
        $this->assertSame('6285299887766', $this->teacherUser->wa_number);
        $this->assertFalse($this->teacherUser->wantsWhatsAppNotification('journal_status'));
        $this->assertTrue($this->teacherUser->wantsWhatsAppNotification('request_status'));
        // SOS tetap wajib true!
        $this->assertTrue($this->teacherUser->wantsWhatsAppNotification('emergency_incident'));
    }

    public function test_custom_self_hosted_gateway_sends_http_request(): void
    {
        WhatsAppGatewayService::resetFake();

        config([
            'services.whatsapp.provider' => 'custom',
            'services.whatsapp.url' => 'http://127.0.0.1:3000/send-message',
            'services.whatsapp.token' => 'vokatrack-secret-key-2026',
        ]);

        Http::fake([
            'http://127.0.0.1:3000/send-message' => Http::response(['success' => true], 200),
        ]);

        $gateway = new WhatsAppGatewayService();
        $result = $gateway->sendMessage('08123456789', 'Uji coba gateway mandiri');

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->url() === 'http://127.0.0.1:3000/send-message'
                && $request->hasHeader('Authorization', 'Bearer vokatrack-secret-key-2026')
                && $request['phone'] === '628123456789'
                && $request['message'] === 'Uji coba gateway mandiri';
        });
    }
}
