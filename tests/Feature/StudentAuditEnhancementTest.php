<?php

namespace Tests\Feature;

use App\Models\Certificate;
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
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\IncidentFollowupNotification;
use App\Services\IncidentReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentAuditEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $studentUser;
    protected Student $student;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected Company $company;
    protected PklPeriod $period;
    protected StudentPkl $spkl;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMK Negeri 1 Test',
            'email' => 'smkn1@test.sch.id',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '021-998877',
            'is_active' => true,
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing Test',
            'email' => 'guru@smkn1test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'school_id' => $this->school->id,
            'nip' => '198001012010011001',
        ]);

        $this->company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Teknologi Digital',
            'address' => 'Kawasan Cyber 2',
            'contact_person' => 'Budi Mentor',
            'phone' => '081234567890',
            'work_days' => '5_days',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'is_pkl_started' => true,
        ]);

        $major = Major::create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
        ]);
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII RPL 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Ahmad Siswa',
            'email' => 'ahmad@smkn1test.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'class_id' => $class->id,
            'nisn' => '0051234567',
            'nis' => '20260101',
            'parent_token' => 'test-parent-token-12345',
        ]);

        $this->period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Semester Gasal 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $this->spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $this->company->id,
            'teacher_id' => $this->teacher->id,
            'pkl_period_id' => $this->period->id,
            'status' => 'active',
            'started_at' => '2026-07-01',
        ]);
    }

    public function test_unassigned_student_can_access_student_portal_pages_without_403()
    {
        // Delete SPKL assignment to test unassigned student state
        $this->spkl->delete();

        $responseHome = $this->actingAs($this->studentUser)->get(route('student.home'));
        $responseHome->assertStatus(200);
        $responseHome->assertSee('Belum Memiliki Penempatan PKL');

        $responseProfile = $this->actingAs($this->studentUser)->get(route('student.profile'));
        $responseProfile->assertStatus(200);
        $responseProfile->assertSee('Belum Ditempatkan');

        $responseAttendance = $this->actingAs($this->studentUser)->get(route('student.attendance'));
        $responseAttendance->assertStatus(200);
        $responseAttendance->assertSee('Belum Memiliki Penempatan PKL');

        $responseJournals = $this->actingAs($this->studentUser)->get(route('student.journals'));
        $responseJournals->assertStatus(200);
        $responseJournals->assertSee('Belum Memiliki Penempatan PKL');

        $responseIncidents = $this->actingAs($this->studentUser)->get(route('student.incidents'));
        $responseIncidents->assertStatus(200);
        $responseIncidents->assertSee('Belum Ada Laporan Kendala');
    }

    public function test_completed_student_can_access_profile_and_view_completed_badge()
    {
        $this->spkl->update(['status' => 'completed']);

        $responseProfile = $this->actingAs($this->studentUser)->get(route('student.profile'));
        $responseProfile->assertStatus(200);
        $responseProfile->assertSee('PKL Selesai');
    }

    public function test_checkin_requires_reason_when_outside_radius()
    {
        Carbon::setTestNow(Carbon::today()->setHour(8)->setMinute(0));

        try {
            // 1. Outside radius without reason should fail validation
            $responseFail = $this->actingAs($this->studentUser)->postJson(route('student.attendance.checkin'), [
                'latitude' => -6.300000,
                'longitude' => 106.900000,
                'is_within_radius' => false,
                'photo' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
                'reason' => '',
            ]);
            $responseFail->assertStatus(422);
            $responseFail->assertJsonValidationErrors(['reason']);

            // 2. Outside radius with valid reason should succeed
            $responseSuccess = $this->actingAs($this->studentUser)->postJson(route('student.attendance.checkin'), [
                'latitude' => -6.300000,
                'longitude' => 106.900000,
                'is_within_radius' => false,
                'photo' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
                'reason' => 'Tugas dinas luar di lokasi proyek klien',
            ]);
            $responseSuccess->assertStatus(200);
            $responseSuccess->assertJson(['success' => true]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_journal_rejects_future_dates_and_allows_draft_deletion()
    {
        // 1. Future date journal should be rejected
        $futureDate = Carbon::tomorrow()->toDateString();
        $responseFuture = $this->actingAs($this->studentUser)->post(route('student.journal.store'), [
            'date' => $futureDate,
            'description' => 'Ini adalah catatan kegiatan yang ditulis untuk hari esok yang belum terjadi.',
            'is_draft' => 0,
        ]);
        $responseFuture->assertSessionHasErrors(['date']);

        // 2. Valid draft journal can be stored
        $today = Carbon::today()->toDateString();
        $responseDraft = $this->actingAs($this->studentUser)->post(route('student.journal.store'), [
            'date' => $today,
            'description' => 'Mengerjakan draft modul autentikasi dan otorisasi API pengguna sistem PKL.',
            'is_draft' => 1,
        ]);
        $responseDraft->assertRedirect(route('student.journals'));

        $journal = Journal::where('student_pkl_id', $this->spkl->id)->where('status', 'draft')->first();
        $this->assertNotNull($journal);

        // 3. Draft journal can be deleted by student
        $responseDelete = $this->actingAs($this->studentUser)->delete(route('student.journal.destroy', $journal));
        $responseDelete->assertRedirect(route('student.journals'));
        $this->assertDatabaseMissing('journals', ['id' => $journal->id]);

        // 4. Approved journal cannot be deleted
        $approvedJournal = Journal::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => $today,
            'description' => 'Jurnal yang sudah disetujui guru pembimbing resmi sekolah.',
            'status' => 'approved',
        ]);

        $responseDeleteApproved = $this->actingAs($this->studentUser)->delete(route('student.journal.destroy', $approvedJournal));
        $responseDeleteApproved->assertStatus(403);
        $this->assertDatabaseHas('journals', ['id' => $approvedJournal->id]);
    }

    public function test_incident_followup_notifies_student()
    {
        Notification::fake();

        $service = app(IncidentReportService::class);
        $report = $service->createReport($this->spkl, [
            'category' => 'curriculum_mismatch',
            'urgency' => 'high',
            'subject' => 'Penugasan tidak sesuai keahlian coding',
            'description' => 'Saya ditugaskan melakukan pengecatan dinding gudang selama 3 hari berturut-turut.',
        ]);

        $followup = $service->addFollowup(
            $report,
            $this->teacherUser,
            'Guru pembimbing telah menghubungi HRD PT Mitra untuk realignment tugas siswa.',
            'in_progress'
        );

        Notification::assertSentTo(
            $this->studentUser,
            IncidentFollowupNotification::class,
            function ($notification) use ($report, $followup) {
                return $notification->report->id === $report->id &&
                       $notification->followup->id === $followup->id;
            }
        );

        // Verify incidents page shows the report and status
        $response = $this->actingAs($this->studentUser)->get(route('student.incidents'));
        $response->assertStatus(200);
        $response->assertSee('Penugasan tidak sesuai keahlian coding');
        $response->assertSee('Sedang Ditindaklanjuti');
        $response->assertSee('Guru pembimbing telah menghubungi HRD');
    }

    public function test_parent_token_regenerates_successfully_via_post()
    {
        $oldToken = $this->student->parent_token;

        $response = $this->actingAs($this->studentUser)->post(route('student.parent-token.regenerate'));
        $response->assertRedirect(route('student.profile'));
        $response->assertSessionHas('success');

        $this->student->refresh();
        $this->assertNotEmpty($this->student->parent_token);
        $this->assertNotEquals($oldToken, $this->student->parent_token);
    }
}
