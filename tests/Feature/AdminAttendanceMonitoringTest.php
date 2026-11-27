<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceEscalation;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\AttendanceEscalationNotification;
use App\Services\WhatsAppGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminAttendanceMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected Major $majorRpl;
    protected Major $majorTkj;
    protected Company $companyA;
    protected Company $companyB;
    protected PklPeriod $period;
    protected StudentPkl $spklMissingAll;
    protected StudentPkl $spklMissingCheckout;
    protected StudentPkl $spklComplete;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'npsn' => '20199955',
            'name' => 'SMK Negeri Monitoring',
            'address' => 'Jl. Monitoring No. 1',
            'phone' => '021-55556666',
            'email' => 'monitoring@test.sch.id',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Monitoring',
            'email' => 'admin@monitortest.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
        Profile::create(['user_id' => $this->adminUser->id, 'gender' => 'L']);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Bu Pembimbing',
            'email' => 'guru@monitortest.sch.id',
            'wa_number' => '081234567890',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        Profile::create(['user_id' => $this->teacherUser->id, 'gender' => 'P', 'phone' => '081234567890']);
        $this->teacher = Teacher::create(['user_id' => $this->teacherUser->id, 'nip' => '19880101']);

        $this->majorRpl = Major::create([
            'school_id' => $this->school->id,
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
        ]);
        $this->majorTkj = Major::create([
            'school_id' => $this->school->id,
            'code' => 'TKJ',
            'name' => 'Teknik Komputer Jaringan',
        ]);

        $classRpl = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorRpl->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);
        $classTkj = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorTkj->id,
            'name' => 'XII TKJ 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $this->companyA = Company::create([
            'school_id' => $this->school->id,
            'created_by_user_id' => $this->adminUser->id,
            'name' => 'PT Alpha Digital',
            'address' => 'Garut',
            'contact_person' => 'HRD Alpha',
            'phone' => '0811111111',
            'work_days' => '5_days',
            'is_pkl_started' => true,
        ]);
        $this->companyB = Company::create([
            'school_id' => $this->school->id,
            'created_by_user_id' => $this->adminUser->id,
            'name' => 'PT Beta Jaringan',
            'address' => 'Bandung',
            'contact_person' => 'HRD Beta',
            'phone' => '0822222222',
            'work_days' => '5_days',
            'is_pkl_started' => true,
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'PKL 2026/2027',
            'start_date' => '2026-11-01',
            'end_date' => '2027-02-28',
            'is_active' => true,
        ]);

        $this->spklMissingAll = $this->makePlacement('Siswa Belum Masuk', '001', $classRpl, $this->companyA);
        $this->spklMissingCheckout = $this->makePlacement('Siswa Belum Pulang', '002', $classTkj, $this->companyB);
        $this->spklComplete = $this->makePlacement('Siswa Lengkap', '003', $classRpl, $this->companyA);

        // Kamis 2026-11-26 (hari kerja aktif)
        $targetDate = '2026-11-26';

        // Siswa 2: sudah masuk, belum pulang, belum jurnal
        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklMissingCheckout->id,
            'date' => $targetDate,
            'status' => 'hadir',
            'checkin_time' => '07:45:00',
            'checkout_time' => null,
        ]);

        // Siswa 3: sudah masuk, sudah pulang, sudah jurnal
        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklComplete->id,
            'date' => $targetDate,
            'status' => 'hadir',
            'checkin_time' => '07:30:00',
            'checkout_time' => '16:15:00',
        ]);
        Journal::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklComplete->id,
            'date' => $targetDate,
            'activity_description' => 'Membuat API endpoint monitoring PKL.',
            'status' => 'submitted',
        ]);
    }

    protected function makePlacement(string $name, string $nisSuffix, SchoolClass $class, Company $company): StudentPkl
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'email' => "siswa{$nisSuffix}@monitortest.sch.id",
            'wa_number' => '081300000' . $nisSuffix,
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Profile::create(['user_id' => $user->id, 'gender' => 'L']);

        $student = Student::create([
            'user_id' => $user->id,
            'class_id' => $class->id,
            'nis' => '10' . $nisSuffix,
            'nisn' => '0099880' . $nisSuffix,
        ]);

        return StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $this->teacher->id,
            'start_date' => '2026-11-01',
            'end_date' => '2027-02-28',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_monitor_and_filter_by_major_company_and_compliance(): void
    {
        $this->actingAs($this->adminUser);
        $date = '2026-11-26';

        // Semua siswa di tanggal kerja aktif
        $resAll = $this->get(route('adminsekolah.attendance-monitoring', ['date' => $date]));
        $resAll->assertOk()
            ->assertSee('Siswa Belum Masuk')
            ->assertSee('Siswa Belum Pulang')
            ->assertSee('Siswa Lengkap')
            ->assertSee('Bu Pembimbing');

        // Filter Belum Absen Masuk
        $resCheckin = $this->get(route('adminsekolah.attendance-monitoring', ['date' => $date, 'compliance' => 'missing_checkin']));
        $resCheckin->assertOk()
            ->assertSee('Siswa Belum Masuk')
            ->assertDontSee('Siswa Belum Pulang')
            ->assertDontSee('Siswa Lengkap');

        // Filter Belum Absen Pulang
        $resCheckout = $this->get(route('adminsekolah.attendance-monitoring', ['date' => $date, 'compliance' => 'missing_checkout']));
        $resCheckout->assertOk()
            ->assertSee('Siswa Belum Pulang')
            ->assertDontSee('Siswa Belum Masuk')
            ->assertDontSee('Siswa Lengkap');

        // Filter Belum Isi Jurnal
        $resJournal = $this->get(route('adminsekolah.attendance-monitoring', ['date' => $date, 'compliance' => 'missing_journal']));
        $resJournal->assertOk()
            ->assertSee('Siswa Belum Masuk')
            ->assertSee('Siswa Belum Pulang')
            ->assertDontSee('Siswa Lengkap');

        // Filter Jurusan TKJ saja
        $resTkj = $this->get(route('adminsekolah.attendance-monitoring', ['date' => $date, 'major_id' => $this->majorTkj->id]));
        $resTkj->assertOk()
            ->assertSee('Siswa Belum Pulang')
            ->assertDontSee('Siswa Belum Masuk')
            ->assertDontSee('Siswa Lengkap');

        // Filter Perusahaan Alpha Digital saja
        $resCompA = $this->get(route('adminsekolah.attendance-monitoring', ['date' => $date, 'company_id' => $this->companyA->id]));
        $resCompA->assertOk()
            ->assertSee('Siswa Belum Masuk')
            ->assertSee('Siswa Lengkap')
            ->assertDontSee('Siswa Belum Pulang');
    }

    public function test_admin_can_escalate_to_teacher_with_in_app_and_whatsapp_log(): void
    {
        Notification::fake();
        WhatsAppGatewayService::fake();

        $this->actingAs($this->adminUser);

        $response = $this->post(route('adminsekolah.attendance-monitoring.escalate'), [
            'student_pkl_id' => $this->spklMissingAll->id,
            'date' => '2026-11-26',
            'missing_types' => ['checkin', 'journal'],
            'note' => 'Mohon hubungi siswa sebelum jam 12:00.',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('attendance_escalations', [
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklMissingAll->id,
            'teacher_id' => $this->teacher->id,
            'sent_in_app' => 1,
            'sent_whatsapp' => 1,
        ]);

        Notification::assertSentTo($this->teacherUser, AttendanceEscalationNotification::class);

        $escalation = AttendanceEscalation::firstOrFail();
        $this->assertEquals(['checkin', 'journal'], $escalation->missing_types);

        WhatsAppGatewayService::resetFake();
    }

    public function test_admin_cannot_escalate_student_from_another_school(): void
    {
        $otherSchool = School::create([
            'npsn' => '20199999',
            'name' => 'SMK Sekolah Lain',
            'address' => 'Solo',
            'phone' => '0271-1111',
            'email' => 'lain@test.sch.id',
            'is_active' => true,
        ]);
        $otherAdmin = User::create([
            'school_id' => $otherSchool->id,
            'name' => 'Admin Lain',
            'email' => 'admin@lain.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $this->actingAs($otherAdmin);

        $response = $this->post(route('adminsekolah.attendance-monitoring.escalate'), [
            'student_pkl_id' => $this->spklMissingAll->id,
            'date' => '2026-11-26',
            'missing_types' => ['checkin'],
        ]);

        $response->assertSessionHasErrors('student_pkl_id');
        $this->assertDatabaseCount('attendance_escalations', 0);
    }
}
