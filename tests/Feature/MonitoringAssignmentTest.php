<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\Journal;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\SupervisionAssignment;
use App\Models\SupervisionAssignmentStudent;
use App\Models\SupervisionVisit;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected User $advisorUser;
    protected Teacher $advisorTeacher;
    protected User $visitorUser;
    protected Teacher $visitorTeacher;
    protected User $kajurUser;
    protected Teacher $kajurTeacher;
    protected User $outsiderUser;
    protected Teacher $outsiderTeacher;

    protected Major $majorTkj;
    protected Major $majorRpl;
    protected Company $companyA;
    protected Company $companyB;
    protected PklPeriod $period;
    protected StudentPkl $spklA;
    protected StudentPkl $spklB;
    protected StudentPkl $spklRpl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'SMK Negeri 2 Bandung',
            'npsn' => '20219999',
            'email' => 'smkn2@example.com',
            'address' => 'Jl. Ciliwung No. 4',
            'phone' => '022-7234285',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin Sekolah',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $this->advisorUser = $this->createTeacherUser('Pembimbing Asli', 'pembimbing@example.com', '198001012009011001');
        $this->advisorTeacher = $this->advisorUser->teacher;

        $this->visitorUser = $this->createTeacherUser('Petugas Monitoring', 'petugas@example.com', '198202022010011002');
        $this->visitorTeacher = $this->visitorUser->teacher;

        $this->kajurUser = $this->createTeacherUser('Kajur TKJ', 'kajur.tkj@example.com', '197903032008011003');
        $this->kajurTeacher = $this->kajurUser->teacher;

        $this->outsiderUser = $this->createTeacherUser('Guru Lain', 'outsider@example.com', '198904042015011004');
        $this->outsiderTeacher = $this->outsiderUser->teacher;

        $this->majorTkj = Major::create([
            'school_id' => $this->school->id,
            'head_teacher_id' => $this->kajurTeacher->id,
            'name' => 'Teknik Komputer dan Jaringan',
            'code' => 'TKJ',
        ]);

        $this->majorRpl = Major::create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
        ]);

        $classTkj = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorTkj->id,
            'name' => 'XII TKJ 1',
            'academic_year' => '2026/2027',
        ]);

        $classRpl = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $this->majorRpl->id,
            'name' => 'XII RPL 1',
            'academic_year' => '2026/2027',
        ]);

        $this->companyA = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Nusantara Network',
            'address' => 'Kawasan Industri A',
            'contact_person' => 'Pak Hendra',
            'phone' => '022-111111',
            'latitude' => -6.9175,
            'longitude' => 107.6191,
            'radius_meters' => 150,
            'is_pkl_started' => true,
        ]);

        $this->companyB = Company::create([
            'school_id' => $this->school->id,
            'name' => 'CV Cloud Solusi',
            'address' => 'Kawasan Industri B',
            'contact_person' => 'Bu Rina',
            'phone' => '022-222222',
            'latitude' => -6.9200,
            'longitude' => 107.6200,
            'radius_meters' => 150,
            'is_pkl_started' => true,
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'PKL 2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->spklA = $this->createStudentPkl('Andi Siswa A', 'andi@example.com', '1001', '001001', $classTkj->id, $this->companyA->id, $this->advisorTeacher->id);
        $this->spklB = $this->createStudentPkl('Bobi Siswa B', 'bobi@example.com', '1002', '001002', $classTkj->id, $this->companyB->id, $this->advisorTeacher->id);
        $this->spklRpl = $this->createStudentPkl('Citra Siswa RPL', 'citra@example.com', '1003', '001003', $classRpl->id, $this->companyA->id, $this->outsiderTeacher->id);

        Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklA->id,
            'date' => '2026-08-10',
            'status' => 'hadir',
        ]);

        Journal::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklA->id,
            'date' => '2026-08-10',
            'activity_description' => 'Setting routing dasar kantor cabang.',
            'status' => 'review_guru',
        ]);

        IncidentReport::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spklA->id,
            'category' => 'administrative',
            'urgency' => 'medium',
            'subject' => 'Adaptasi Shift',
            'description' => 'Memerlukan penyesuaian jadwal piket.',
            'status' => 'submitted',
        ]);
    }

    private function createTeacherUser(string $name, string $email, string $nip): User
    {
        $user = User::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);

        Teacher::create([
            'user_id' => $user->id,
            'nip' => $nip,
        ]);

        return $user->fresh('teacher');
    }

    private function createStudentPkl(
        string $name,
        string $email,
        string $nis,
        string $nisn,
        int $classId,
        int $companyId,
        int $teacherId
    ): StudentPkl {
        $user = User::create([
            'school_id' => $this->school->id,
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'class_id' => $classId,
            'nis' => $nis,
            'nisn' => $nisn,
        ]);

        return StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'company_id' => $companyId,
            'pkl_period_id' => $this->period->id,
            'teacher_id' => $teacherId,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_assign_one_teacher_to_multiple_companies_and_readonly_briefing_closes_after_report(): void
    {
        // 1. Admin menugaskan Petugas Monitoring ke Company A dan Company B sekaligus pada Monitoring 1
        $this->actingAs($this->adminUser);

        $assignRes = $this->post(route('adminsekolah.supervisions.assignments.store'), [
            'teacher_id' => $this->visitorTeacher->id,
            'pkl_period_id' => $this->period->id,
            'stage' => 'monitoring_1',
            'assignment_number' => 'ST-001/2026',
            'student_pkl_ids' => [$this->spklA->id, $this->spklB->id],
        ]);

        $assignRes->assertRedirect();
        $assignRes->assertSessionHas('success');

        $assignment = SupervisionAssignment::first();
        $this->assertNotNull($assignment);
        $this->assertEquals($this->visitorTeacher->id, $assignment->teacher_id);
        $this->assertCount(2, $assignment->items);

        // 2. Pembimbing asli (advisor) TIDAK boleh mengisi monitoring_1 untuk spklA karena sudah dialihkan
        $this->actingAs($this->advisorUser);
        $blockedAdvisorRes = $this->post(route('guru.supervisions.store'), [
            'company_id' => $this->companyA->id,
            'pkl_period_id' => $this->period->id,
            'stage' => 'monitoring_1',
            'visit_date' => '2026-08-15',
            'student_progress_summary' => 'Mencoba mengisi meski tugas dialihkan ke petugas lain.',
            'company_feedback' => 'Masukan industri tidak seharusnya tersimpan dari pembimbing.',
        ]);
        $blockedAdvisorRes->assertSessionHasErrors('company_id');
        $this->assertDatabaseCount('supervision_visits', 0);

        // 3. Petugas monitoring membuka halaman briefing read-only untuk Company A sebelum melapor
        $this->actingAs($this->visitorUser);
        $briefingA = $this->get(route('guru.supervisions.briefing', [$assignment->id, $this->companyA->id]));
        $briefingA->assertOk();
        $briefingA->assertSee('Andi Siswa A');
        $briefingA->assertSee('Pembimbing Asli');
        $briefingA->assertSee('Setting routing dasar kantor cabang.');
        $briefingA->assertSee('Adaptasi Shift');

        // 4. Petugas monitoring mengirim laporan kunjungan untuk Company A saja
        $visitRes = $this->post(route('guru.supervisions.store'), [
            'company_id' => $this->companyA->id,
            'pkl_period_id' => $this->period->id,
            'supervision_assignment_id' => $assignment->id,
            'stage' => 'monitoring_1',
            'visit_date' => '2026-08-15',
            'student_progress_summary' => 'Siswa bekerja sangat disiplin dan mampu mengikuti SOP jaringan.',
            'company_feedback' => 'Performa baik, komunikasi dengan tim teknis lancar.',
            'follow_up_agreement' => 'Lanjutkan pendalaman konfigurasi VLAN.',
        ]);

        $visitRes->assertRedirect(route('guru.supervisions'));
        $visitRes->assertSessionHas('success');

        $visit = SupervisionVisit::first();
        $this->assertNotNull($visit);
        $this->assertEquals($assignment->id, $visit->supervision_assignment_id);
        $this->assertTrue($visit->studentPkls->contains('id', $this->spklA->id));
        $this->assertFalse($visit->studentPkls->contains('id', $this->spklB->id));

        // 5. Akses read-only untuk Company A OTOMATIS TERTUTUP, sedangkan Company B masih terbuka!
        $closedBriefingA = $this->get(route('guru.supervisions.briefing', [$assignment->id, $this->companyA->id]));
        $closedBriefingA->assertRedirect(route('guru.supervisions'));
        $closedBriefingA->assertSessionHas('error');

        $stillOpenBriefingB = $this->get(route('guru.supervisions.briefing', [$assignment->id, $this->companyB->id]));
        $stillOpenBriefingB->assertOk();
        $stillOpenBriefingB->assertSee('Bobi Siswa B');

        // 6. Pembimbing asli tetap bisa melihat riwayat kunjungan & mengunduh Berita Acara PDF
        $this->actingAs($this->advisorUser);
        $advisorPage = $this->get(route('guru.supervisions'));
        $advisorPage->assertOk();
        $advisorPage->assertSee('PT Nusantara Network');
        $advisorPage->assertSee('Petugas Monitoring');

        $advisorPdf = $this->get(route('guru.supervisions.pdf', $visit->id));
        $advisorPdf->assertOk();
        $this->assertStringContainsString('application/pdf', $advisorPdf->headers->get('Content-Type'));

        // 7. Guru lain yang tidak terkait ditolak (403)
        $this->actingAs($this->outsiderUser);
        $this->get(route('guru.supervisions.pdf', $visit->id))->assertForbidden();

        // 8. Admin sekolah bisa melihat halaman manajemen penugasan & monitoring serta mengunduh PDF
        $this->actingAs($this->adminUser);
        $this->get(route('adminsekolah.supervisions'))
            ->assertOk()
            ->assertSee('ST-001/2026')
            ->assertSee('PT Nusantara Network');

        $this->get(route('adminsekolah.supervisions.pdf', $visit->id))->assertOk();
    }

    public function test_kajur_can_be_assigned_and_can_assign_within_own_major_only(): void
    {
        // Admin menugaskan Kajur TKJ untuk monitoring siswa lintas pembimbing di Company A
        $this->actingAs($this->adminUser);
        $this->post(route('adminsekolah.supervisions.assignments.store'), [
            'teacher_id' => $this->kajurTeacher->id,
            'pkl_period_id' => $this->period->id,
            'stage' => 'monitoring_1',
            'student_pkl_ids' => [$this->spklA->id],
        ])->assertSessionHas('success');

        // Kajur login -> bisa melihat tugasnya di panel guru dan melakukan laporan kunjungan
        $this->actingAs($this->kajurUser);
        $assignment = SupervisionAssignment::first();

        $this->get(route('guru.supervisions.briefing', [$assignment->id, $this->companyA->id]))
            ->assertOk()
            ->assertSee('Andi Siswa A');

        $this->post(route('guru.supervisions.store'), [
            'company_id' => $this->companyA->id,
            'pkl_period_id' => $this->period->id,
            'supervision_assignment_id' => $assignment->id,
            'stage' => 'monitoring_1',
            'visit_date' => '2026-08-18',
            'student_progress_summary' => 'Kajur meninjau langsung kompetensi jaringan siswa di industri.',
            'company_feedback' => 'Industri puas dengan kesiapan teknis siswa TKJ.',
        ])->assertSessionHas('success');

        $visit = SupervisionVisit::first();

        // Kajur bisa lihat & unduh PDF di panel Kajur
        $this->get(route('kajur.supervisions'))
            ->assertOk()
            ->assertSee('PT Nusantara Network');

        $this->get(route('kajur.supervisions.pdf', $visit->id))
            ->assertOk();

        // Kajur bisa membuat penugasan Monitoring 2 untuk siswa jurusannya (TKJ)
        $this->post(route('kajur.supervisions.assignments.store'), [
            'teacher_id' => $this->visitorTeacher->id,
            'pkl_period_id' => $this->period->id,
            'stage' => 'monitoring_2',
            'student_pkl_ids' => [$this->spklA->id],
        ])->assertSessionHas('success');

        // Kajur TIDAK bisa membuat penugasan untuk siswa jurusan RPL (di luar jurusannya)
        $this->post(route('kajur.supervisions.assignments.store'), [
            'teacher_id' => $this->visitorTeacher->id,
            'pkl_period_id' => $this->period->id,
            'stage' => 'monitoring_2',
            'student_pkl_ids' => [$this->spklRpl->id],
        ])->assertSessionHasErrors('company_ids');
    }

    public function test_advisor_fallback_and_incidental_visit_work_for_two_monitoring_model(): void
    {
        // Tanpa penugasan khusus, pembimbing asli langsung bisa input Monitoring 1 & Monitoring 2
        $this->actingAs($this->advisorUser);

        $this->post(route('guru.supervisions.store'), [
            'company_id' => $this->companyA->id,
            'pkl_period_id' => $this->period->id,
            'stage' => 'monitoring_1',
            'visit_date' => '2026-08-05',
            'student_progress_summary' => 'Monitoring pertama oleh pembimbing asli berjalan lancar.',
            'company_feedback' => 'Siswa aktif mengikuti pengarahan kerja.',
        ])->assertSessionHas('success');

        // Monitoring 1 kedua kali untuk siswa yang sama otomatis ditolak
        $this->post(route('guru.supervisions.store'), [
            'company_id' => $this->companyA->id,
            'pkl_period_id' => $this->period->id,
            'stage' => 'monitoring_1',
            'visit_date' => '2026-08-06',
            'student_progress_summary' => 'Percobaan duplikat monitoring 1 pada siswa yang sama.',
            'company_feedback' => 'Masukan duplikat yang seharusnya ditolak sistem.',
        ])->assertSessionHasErrors('company_id');

        // Kunjungan insidental tetap dapat dicatat di luar 2x monitoring rutin
        $this->post(route('guru.supervisions.store'), [
            'company_id' => $this->companyA->id,
            'pkl_period_id' => $this->period->id,
            'stage' => 'insidental',
            'visit_date' => '2026-08-20',
            'student_progress_summary' => 'Kunjungan insidental menindaklanjuti penyesuaian jadwal shift.',
            'company_feedback' => 'Sudah disepakati jadwal shift baru dengan HRD.',
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('supervision_visits', 2);
        $this->assertEquals(1, SupervisionAssignmentStudent::count() + 1);
    }
}
