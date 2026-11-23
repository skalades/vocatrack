<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSubscription;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\SubscriptionPlan;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AssessmentService;
use App\Services\AttendanceService;
use App\Services\CertificateService;
use App\Services\JournalService;
use App\Services\StorageQuotaService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ConcurrencyAndRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    protected function setupEnvironment(): array
    {
        $school = School::create([
            'npsn' => '20209203',
            'name' => 'SMK Negeri 2 Garut',
            'address' => 'Jl. Dr. Suherman No. 90, Garut',
            'phone' => '0262-233131',
            'email' => 'info@smkn2garut.sch.id',
            'is_active' => true,
        ]);

        $major = Major::create([
            'school_id' => $school->id,
            'code' => 'GP',
            'name' => 'Geologi Pertambangan',
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'major_id' => $major->id,
            'name' => 'XII GP 1',
            'grade_level' => 'XII',
            'academic_year' => '2026/2027',
        ]);

        $company = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Aneka Tambang Tbk - Geomin',
            'address' => 'Site Eksplorasi Garut',
            'contact_person' => 'Ir. Hendra Gunawan',
            'phone' => '08123456789',
            'gps_enabled' => false,
            'work_days' => '5_days',
        ]);

        $period = PklPeriod::create([
            'school_id' => $school->id,
            'name' => 'PKL Geologi Pertambangan 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Herdi Pebryana Putra, S.T',
            'email' => 'herdi@smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'nip' => '197505102000031002']);

        $dudiUser = User::create([
            'school_id' => $school->id,
            'name' => 'Ir. Hendra Gunawan, S.T.',
            'email' => 'hendra@antam.com',
            'password' => bcrypt('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        $supervisor = Supervisor::create(['user_id' => $dudiUser->id, 'company_id' => $company->id]);

        $studentUser = User::create([
            'school_id' => $school->id,
            'name' => 'Fajar Nugraha',
            'email' => 'fajar@smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'nis' => '240101',
            'nisn' => '0067890123',
        ]);

        $spkl = StudentPkl::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'supervisor_id' => $supervisor->id,
            'status' => 'active',
        ]);

        return compact('school', 'company', 'spkl', 'studentUser', 'teacherUser', 'dudiUser');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_concurrent_checkin_is_prevented_by_unique_constraint(): void
    {
        $env = $this->setupEnvironment();
        $spkl = $env['spkl'];
        $service = new AttendanceService();

        Carbon::setTestNow(Carbon::create(2026, 8, 10, 7, 30, 0));

        // First checkin succeeds
        $att1 = $service->recordCheckIn($spkl, []);
        $this->assertEquals('hadir', $att1->status);

        // Immediate duplicate checkin attempt throws ValidationException gracefully
        $this->expectException(ValidationException::class);
        $service->recordCheckIn($spkl, []);
    }

    public function test_double_checkout_is_prevented_with_row_locking(): void
    {
        $env = $this->setupEnvironment();
        $spkl = $env['spkl'];
        $service = new AttendanceService();

        Carbon::setTestNow(Carbon::create(2026, 8, 10, 7, 30, 0));
        $service->recordCheckIn($spkl, []);

        Carbon::setTestNow(Carbon::create(2026, 8, 10, 16, 30, 0));
        // First checkout succeeds
        $checkout = $service->recordCheckOut($spkl, ['checkout_notes' => 'Selesai pemetaan core drill']);
        $this->assertNotNull($checkout->checkout_time);

        // Second checkout immediately throws ValidationException
        $this->expectException(ValidationException::class);
        $service->recordCheckOut($spkl, ['checkout_notes' => 'Attempt duplicate checkout']);
    }

    public function test_journal_concurrent_double_approval_prevention(): void
    {
        $env = $this->setupEnvironment();
        $spkl = $env['spkl'];
        $dudiUser = $env['dudiUser'];
        $journalService = new JournalService();

        $journal = Journal::create([
            'school_id' => $spkl->school_id,
            'student_pkl_id' => $spkl->id,
            'date' => '2026-08-10',
            'activity_description' => 'Eksplorasi geologi batuan andesit dan pemetaan strike/dip di quarry pasir batu Garut.',
            'status' => 'submitted',
        ]);

        // First DUDI approval succeeds
        $journalService->approve($journal, $dudiUser, 'Kerja lapangan sangat memuaskan.');
        $this->assertEquals('review_guru', $journal->fresh()->status);

        // Second concurrent approval attempt by DUDI must throw exception because status transitioned
        $this->expectException(ValidationException::class);
        $journalService->approve($journal->fresh(), $dudiUser, 'Duplicate approve attempt');
    }

    public function test_concurrent_assessment_scoring_by_teacher_and_dudi(): void
    {
        $env = $this->setupEnvironment();
        $spkl = $env['spkl'];
        $assessmentService = new AssessmentService();

        // 1. DUDI saves scores first
        $assessment = $assessmentService->saveSupervisorAssessment($spkl, [
            'score_dudi_discipline' => 90.00,
            'score_dudi_technical' => 85.00,
            'score_dudi_initiative' => 80.00,
            'notes_dudi' => 'Kompetensi penggunaan kompas geologi dan identifikasi mineral sangat baik.',
        ]);

        $this->assertDatabaseHas('assessments', [
            'student_pkl_id' => $spkl->id,
            'score_dudi_discipline' => 90.00,
            'score_dudi_technical' => 85.00,
            'score_dudi_initiative' => 80.00,
        ]);

        // 2. Teacher saves report score concurrently
        $assessmentUpdated = $assessmentService->saveTeacherAssessment($spkl, [
            'score_teacher_report' => 95.00,
            'notes_teacher' => 'Laporan observasi geologi dan penampang stratigrafi tersusun rapi.',
        ]);

        // Average DUDI = (90 + 85 + 80) / 3 = 85.00
        // Weight: 60% DUDI : 40% Guru -> (85 * 0.6) + (95 * 0.4) = 51 + 38 = 89.00
        $this->assertEquals(89.00, (float) $assessmentUpdated->final_score);
        $this->assertEquals(90.00, (float) $assessmentUpdated->score_dudi_discipline);
        $this->assertEquals(95.00, (float) $assessmentUpdated->score_teacher_report);
    }

    public function test_attendance_request_cannot_be_processed_twice(): void
    {
        $env = $this->setupEnvironment();
        $spkl = $env['spkl'];
        $teacherUser = $env['teacherUser'];

        $attRequest = AttendanceRequest::create([
            'school_id' => $spkl->school_id,
            'student_pkl_id' => $spkl->id,
            'type' => 'dinas_luar',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-16',
            'reason' => 'Pemetaan geologi singkapan batuan di Cikajang Garut.',
            'status' => 'pending',
        ]);

        $this->actingAs($teacherUser);

        // First process
        $response1 = $this->post(route('guru.requests.process', $attRequest->id), [
            'action' => 'approve',
            'reviewer_notes' => 'Diizinkan dengan pengawasan mentor DUDI.',
        ]);
        $response1->assertRedirect();
        $this->assertEquals('approved', $attRequest->fresh()->status);

        // Second process (concurrent or duplicate click) must fail with error
        $response2 = $this->post(route('guru.requests.process', $attRequest->id), [
            'action' => 'reject',
            'reviewer_notes' => 'Duplicate attempt to reject.',
        ]);
        $response2->assertSessionHasErrors(['status']);
    }

    public function test_approved_journal_cannot_be_overwritten_by_student(): void
    {
        $env = $this->setupEnvironment();
        $spkl = $env['spkl'];
        $studentUser = $env['studentUser'];

        $journal = Journal::create([
            'school_id' => $spkl->school_id,
            'student_pkl_id' => $spkl->id,
            'date' => '2026-08-10',
            'activity_description' => 'Original approved activity description for mining geology field work.',
            'status' => 'approved',
        ]);

        $this->actingAs($studentUser);

        $response = $this->post(route('student.journal.store'), [
            'date' => '2026-08-10',
            'activity_description' => 'Mencoba mengubah isi jurnal yang sudah diapprove oleh mentor industri dan guru.',
            'action_type' => 'submit',
        ]);

        $response->assertSessionHasErrors(['date']);
        $this->assertEquals('approved', $journal->fresh()->status);
        $this->assertEquals('Original approved activity description for mining geology field work.', $journal->fresh()->activity_description);
    }

    public function test_student_quota_toctou_prevention_with_locking(): void
    {
        $env = $this->setupEnvironment();
        $school = $env['school'];
        $subService = app(SubscriptionService::class);

        $plan = SubscriptionPlan::create([
            'code' => 'starter-plan',
            'name' => 'Starter PKL',
            'price' => 500000,
            'max_students' => 1, // Quota only allows 1 student
            'max_storage_mb' => 1024,
        ]);

        $sub = SchoolSubscription::create([
            'school_id' => $school->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addYear(),
            'price_paid' => 500000,
            'student_quota' => 1, // Only 1 student allowed
            'status' => 'active',
        ]);

        // There is already 1 student created in setupEnvironment.
        // Therefore adding 1 more student must throw ValidationException under enforceQuotaWithLock
        $this->expectException(ValidationException::class);
        \Illuminate\Support\Facades\DB::transaction(function () use ($subService, $school) {
            $subService->enforceQuotaWithLock($school, 1);
        });
    }

    public function test_storage_quota_atomic_sync_on_upload_and_deletion(): void
    {
        $env = $this->setupEnvironment();
        $school = $env['school'];
        $storageService = app(StorageQuotaService::class);

        $storageService->recordUpload($school, 102400); // 100 KB
        $this->assertEquals(102400, (int) $school->fresh()->storage_used_bytes);
        $this->assertEquals(102400, (int) \Illuminate\Support\Facades\Cache::get("school_storage_bytes_{$school->id}"));

        $storageService->recordDeletion($school, 20400); // 20 KB
        $this->assertEquals(82000, (int) $school->fresh()->storage_used_bytes);
        $this->assertEquals(82000, (int) \Illuminate\Support\Facades\Cache::get("school_storage_bytes_{$school->id}"));
    }

    public function test_certificate_generation_with_concurrency_lock(): void
    {
        $env = $this->setupEnvironment();
        $spkl = $env['spkl'];
        $certService = app(CertificateService::class);

        Assessment::create([
            'school_id' => $spkl->school_id,
            'student_pkl_id' => $spkl->id,
            'score_dudi_discipline' => 90,
            'score_dudi_technical' => 88,
            'score_dudi_initiative' => 85,
            'score_teacher_report' => 92,
            'final_score' => 89.5,
        ]);

        $cert1 = $certService->generateForStudentPkl($spkl);
        $this->assertNotNull($cert1->certificate_number);

        // Generating again for same student returns existing record idempotently
        $cert2 = $certService->generateForStudentPkl($spkl);
        $this->assertEquals($cert1->id, $cert2->id);
        $this->assertEquals($cert1->certificate_number, $cert2->certificate_number);
    }
}
