<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\PlatformSetting;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSubscription;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\SubscriptionPlan;
use App\Models\Teacher;
use App\Models\User;
use App\Services\BackupService;
use App\Services\StorageQuotaService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SaaSEnhancementsAndAuditFixTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected School $school;
    protected User $schoolAdmin;
    protected SubscriptionPlan $trialPlan;
    protected SubscriptionPlan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@vokatrack.id',
            'password' => bcrypt('password'),
            'role' => 'super-admin',
            'is_active' => true,
        ]);

        $this->trialPlan = SubscriptionPlan::create([
            'code' => 'trial-basic',
            'name' => 'Paket Trial Dasar',
            'price' => 0,
            'billing_period' => 'monthly',
            'max_students' => 50,
            'max_storage_mb' => 2048,
            'features' => [
                'attendance' => true,
                'journal' => true,
                'assessment' => true,
                'certificate' => false,
                'whatsapp_gateway' => false,
                'custom_domain' => false,
            ],
            'description' => 'Paket demo',
            'is_popular' => false,
            'is_active' => true,
        ]);

        $this->proPlan = SubscriptionPlan::create([
            'code' => 'pro-enterprise',
            'name' => 'Paket Pro Enterprise',
            'price' => 5000000,
            'billing_period' => 'yearly',
            'max_students' => 500,
            'max_storage_mb' => 10240,
            'features' => [
                'attendance' => true,
                'journal' => true,
                'assessment' => true,
                'certificate' => true,
                'whatsapp_gateway' => true,
                'custom_domain' => true,
            ],
            'description' => 'Paket lengkap',
            'is_popular' => true,
            'is_active' => true,
        ]);

        $this->school = School::create([
            'npsn' => '12345678',
            'name' => 'SMK Negeri 1 Test',
            'address' => 'Jl. Pendidikan No. 1',
            'phone' => '081234567890',
            'email' => 'smkn1@test.sch.id',
            'is_active' => true,
            'storage_used_bytes' => 0,
        ]);

        $this->schoolAdmin = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin SMKN 1',
            'email' => 'admin@smkn1.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
    }

    public function test_feature_gating_blocks_and_allows_certificate_generation(): void
    {
        // 1. Assign Trial Plan (certificate => false)
        app(SubscriptionService::class)->subscribeSchool(
            $this->school,
            $this->trialPlan,
            Carbon::today(),
            50,
            0,
            'trial'
        );

        $this->assertFalse($this->school->hasFeature('certificate'));
        $this->assertTrue($this->school->hasFeature('attendance'));

        // Setup Student & SPKL for Certificate Test
        $major = Major::create(['school_id' => $this->school->id, 'name' => 'RPL', 'code' => 'RPL']);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'major_id' => $major->id, 'name' => 'XII RPL 1', 'academic_year' => '2026/2027']);
        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Budi Santoso',
            'email' => 'budi@smkn1.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'nis' => '1001',
            'nisn' => '0012345678',
        ]);
        // Setup Teacher
        $teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing',
            'email' => 'guru@smkn1.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '198501012010011001',
        ]);

        $company = Company::create(['school_id' => $this->school->id, 'name' => 'PT Teknologi', 'address' => 'Bandung', 'contact_person' => 'Budi', 'phone' => '08123456789']);
        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Periode 2026',
            'start_date' => Carbon::today()->subMonths(3),
            'end_date' => Carbon::today(),
            'is_active' => true,
        ]);
        $spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'status' => 'completed',
        ]);

        // Attempt generating certificate with certificate => false should be blocked
        $response = $this->actingAs($this->schoolAdmin)->post(route('adminsekolah.certificates.generate'), [
            'student_pkl_id' => $spkl->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('tidak aktif', session('error'));

        // 2. Upgrade to Pro Plan (certificate => true)
        app(SubscriptionService::class)->subscribeSchool(
            $this->school,
            $this->proPlan,
            Carbon::today(),
            500,
            5000000,
            'active'
        );

        $this->assertTrue($this->school->fresh()->hasFeature('certificate'));
    }

    public function test_storage_quota_enforcement_and_incremental_tracking(): void
    {
        $quotaService = app(StorageQuotaService::class);

        // 1. Initially storage is 0, canUpload returns true
        $this->assertTrue($quotaService->canUpload($this->school, 1024));

        // 2. Record upload increments storage_used_bytes
        $quotaService->recordUpload($this->school, 1048576); // 1 MB
        $this->assertEquals(1048576, $this->school->fresh()->storage_used_bytes);

        // 3. Record deletion decrements storage_used_bytes
        $quotaService->recordDeletion($this->school, 524288); // 0.5 MB
        $this->assertEquals(524288, $this->school->fresh()->storage_used_bytes);

        // 4. Fill storage to quota limit
        $maxBytes = $this->school->storage_quota_mb * 1024 * 1024;
        $this->school->update(['storage_used_bytes' => $maxBytes]);

        // Now canUpload for 100 bytes must be false
        $this->assertFalse($quotaService->canUpload($this->school->fresh(), 100));
    }

    public function test_tenant_self_service_billing_portal_and_invoice_download(): void
    {
        // Subscribe school
        $sub = app(SubscriptionService::class)->subscribeSchool(
            $this->school,
            $this->proPlan,
            Carbon::today(),
            500,
            5000000,
            'active'
        );

        $invoice = Invoice::where('school_id', $this->school->id)->first();
        $this->assertNotNull($invoice);

        // School Admin can view billing page
        $response = $this->actingAs($this->schoolAdmin)->get(route('adminsekolah.billing'));
        $response->assertStatus(200);
        $response->assertSee('Paket Pro Enterprise');
        $response->assertSee($invoice->invoice_number);

        // School Admin can download their invoice PDF
        $responsePdf = $this->actingAs($this->schoolAdmin)->get(route('adminsekolah.invoices.pdf', $invoice->id));
        $responsePdf->assertStatus(200);
        $responsePdf->assertHeader('content-type', 'application/pdf');

        // Other school admin cannot access this invoice (403 forbidden)
        $otherSchool = School::create([
            'npsn' => '87654321',
            'name' => 'SMK Negeri 2 Other',
            'address' => 'Jl. Lain',
            'phone' => '0899999999',
            'email' => 'other@sch.id',
            'is_active' => true,
        ]);
        $otherAdmin = User::create([
            'school_id' => $otherSchool->id,
            'name' => 'Admin Other',
            'email' => 'admin@other.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $this->actingAs($otherAdmin)
            ->get(route('adminsekolah.invoices.pdf', $invoice->id))
            ->assertStatus(403);
    }

    public function test_database_backup_and_restore_workflow(): void
    {
        $backupService = app(BackupService::class);

        // 1. Create database backup
        $backup = $backupService->createDatabaseBackup();
        $this->assertFileExists($backup['path']);

        // 2. Modify data
        $originalName = $this->school->name;
        $this->school->update(['name' => 'MODIFIED NAME FOR TEST']);
        $this->assertEquals('MODIFIED NAME FOR TEST', $this->school->fresh()->name);

        // 3. Restore backup
        $result = $backupService->restoreDatabaseBackup($backup['filename']);
        $this->assertEquals($backup['filename'], $result['filename']);
        $this->assertGreaterThan(0, $result['tables_count']);

        // 4. Verify original name restored
        $restoredSchool = School::find($this->school->id);
        $this->assertEquals($originalName, $restoredSchool->name);

        // Clean up backup file
        $backupService->deleteBackup($backup['filename']);
    }

    public function test_saas_evaluate_subscriptions_artisan_command(): void
    {
        // 1. Create a subscription that ended 3 days ago (should enter grace_period)
        $sub = SchoolSubscription::create([
            'school_id' => $this->school->id,
            'plan_id' => $this->proPlan->id,
            'plan_name' => $this->proPlan->name,
            'start_date' => Carbon::today()->subMonths(12),
            'end_date' => Carbon::today()->subDays(3),
            'price_paid' => 5000000,
            'student_quota' => 500,
            'storage_quota_mb' => 10240,
            'status' => 'active',
        ]);

        Artisan::call('saas:evaluate-subscriptions');
        // dump(Artisan::output());

        $this->assertEquals('grace_period', $sub->fresh()->status);

        // 2. Set end_date 10 days ago (grace period is 7 days, so should become expired)
        $sub->update([
            'end_date' => Carbon::today()->subDays(10),
            'status' => 'grace_period',
        ]);

        Artisan::call('saas:evaluate-subscriptions');

        $this->assertEquals('expired', $sub->fresh()->status);
    }

    public function test_student_mobile_impersonation_banner_and_leave_workflow(): void
    {
        // Setup student
        $major = Major::create(['school_id' => $this->school->id, 'name' => 'TKJ', 'code' => 'TKJ']);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'major_id' => $major->id, 'name' => 'XII TKJ 1', 'academic_year' => '2026/2027']);
        $studentUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Siswa Impersonate Test',
            'email' => 'siswa.test@smkn1.sch.id',
            'password' => bcrypt('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_id' => $class->id,
            'nis' => '9999',
            'nisn' => '9999999999',
        ]);
        $company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Mitratek',
            'address' => 'Jakarta',
            'contact_person' => 'Siti',
            'phone' => '08123456789',
        ]);
        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Periode 2026/2027',
            'start_date' => Carbon::today()->subMonths(1),
            'end_date' => Carbon::today()->addMonths(2),
            'is_active' => true,
        ]);
        $teacherUser2 = User::create([
            'school_id' => $this->school->id,
            'name' => 'Guru Pembimbing 2',
            'email' => 'guru2@smkn1.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        $teacher2 = Teacher::create([
            'user_id' => $teacherUser2->id,
            'nip' => '198501012010011002',
        ]);
        StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher2->id,
            'company_id' => $company->id,
            'pkl_period_id' => $period->id,
            'status' => 'active',
        ]);

        // Super Admin starts impersonating student
        $this->actingAs($this->superAdmin)
            ->post(route('impersonate.start', $studentUser->id))
            ->assertRedirect(route('student.home'));

        // Visit student home page
        $response = $this->get(route('student.home'));
        $response->assertStatus(200);
        // Verify impersonation warning header is rendered in mobile layout
        $response->assertSee('Mode Impersonasi:');
        $response->assertSee('Siswa Impersonate Test');
        $response->assertSee('Kembali');

        // Leave impersonation
        $leaveResponse = $this->post(route('impersonate.leave'));
        $leaveResponse->assertRedirect(route('superadmin.dashboard'));
        $this->assertEquals($this->superAdmin->id, auth()->id());
    }
}
