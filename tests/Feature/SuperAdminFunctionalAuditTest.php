<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PlatformSetting;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\Student;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuperAdminFunctionalAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected SubscriptionPlan $trialPlan;
    protected SubscriptionPlan $proPlan;
    protected School $school1;
    protected School $school2;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Superadmin User
        $this->superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@vokatrack.id',
            'password' => bcrypt('password'),
            'role' => 'super-admin',
            'is_active' => true,
        ]);

        // 2. Create Plans
        $this->trialPlan = SubscriptionPlan::create([
            'code' => 'trial-14d',
            'name' => 'Trial Evaluasi (14 Hari)',
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
            'description' => 'Paket demo evaluasi sistem',
            'is_popular' => false,
            'is_active' => true,
        ]);

        $this->proPlan = SubscriptionPlan::create([
            'code' => 'pro-yearly',
            'name' => 'Paket Pro Tahunan (Rekomendasi)',
            'price' => 6000000,
            'billing_period' => 'yearly',
            'max_students' => 300,
            'max_storage_mb' => 20480,
            'features' => [
                'attendance' => true,
                'journal' => true,
                'assessment' => true,
                'certificate' => true,
                'whatsapp_gateway' => true,
                'custom_domain' => false,
            ],
            'description' => 'Paket pro',
            'is_popular' => true,
            'is_active' => true,
        ]);

        // 3. Create Schools & subscribe to Demo / Trial
        $subService = app(SubscriptionService::class);

        $this->school1 = School::create([
            'npsn' => '20209201',
            'name' => 'SMK Negeri 1 Garut',
            'address' => 'Jl. Cimanuk No. 309 A',
            'phone' => '(0262) 233316',
            'email' => 'info@smknegeri1garut.sch.id',
            'is_active' => true,
        ]);
        User::create([
            'school_id' => $this->school1->id,
            'name' => 'Admin SMKN 1 Garut',
            'email' => 'admin@smknegeri1garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
        $subService->subscribeSchool(
            $this->school1,
            $this->trialPlan,
            Carbon::today()->subDays(2),
            50,
            0,
            'trial',
            'Demo trial SMKN 1'
        );

        $this->school2 = School::create([
            'npsn' => '20209203',
            'name' => 'SMK Negeri 2 Garut',
            'address' => 'Jl. Prof. Dr. Suherman No. 90',
            'phone' => '(0262) 233131',
            'email' => 'info@smkn2garut.sch.id',
            'is_active' => true,
        ]);
        User::create([
            'school_id' => $this->school2->id,
            'name' => 'Admin SMKN 2 Garut',
            'email' => 'admin@smkn2garut.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
        $subService->subscribeSchool(
            $this->school2,
            $this->trialPlan,
            Carbon::today()->subDays(2),
            50,
            0,
            'trial',
            'Demo trial SMKN 2'
        );
    }

    /**
     * 1. Audit Dashboard SaaS Metrics when all registered schools are on Demo Package
     */
    public function test_dashboard_metrics_and_view_with_demo_packages(): void
    {
        $metrics = app(SubscriptionService::class)->getSaaSMetrics();

        $this->assertEquals(2, $metrics['total_schools']);
        $this->assertEquals(2, $metrics['active_schools']);
        $this->assertEquals(0, $metrics['active_tenants'], 'Paying tenants should be 0');
        $this->assertEquals(2, $metrics['trial_tenants'], 'Demo/Trial tenants should be 2');
        $this->assertEquals(2, $metrics['expiring_soon'], 'Trial schools expiring within 14 days counted in expiring soon');
        $this->assertEquals(0, $metrics['total_revenue'], 'Revenue from trial packages should be 0');
        $this->assertEquals(0, $metrics['mrr_estimate'], 'MRR from trial packages should be 0');

        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Pusat Kendali Sewa Platform');
        $response->assertSee('2 Masa Trial');
        $response->assertSee('Trial 14 Hari');
        $response->assertSee('Pemberitahuan Masa Sewa Menjelang Berakhir');
    }

    /**
     * 2. Audit Schools List and Filter for Demo / Trial Package
     */
    public function test_schools_list_displays_demo_badge_and_filters(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.schools'));
        $response->assertStatus(200);
        $response->assertSee('Daftar Tenant Sekolah Mitra');
        $response->assertSee('Trial Evaluasi (14 Hari)');
        $response->assertSee('Gratis / Trial');
        $response->assertSee('Hanya Paket Demo / Trial');
    }

    /**
     * 3. Audit New School Registration with Demo Package
     */
    public function test_superadmin_can_register_new_school_with_demo_plan(): void
    {
        $payload = [
            'npsn' => '20262128',
            'name' => 'SMK Negeri 10 Garut',
            'address' => 'Jl. Raya Garut - Tasikmalaya Km. 08',
            'phone' => '(0262) 4891303',
            'email' => 'info@smknegeri10garut.sch.id',
            'admin_name' => 'Admin SMKN 10',
            'admin_email' => 'admin@smknegeri10garut.sch.id',
            'plan_id' => $this->trialPlan->id,
            'custom_quota' => 50,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.schools.store'), $payload);
        $response->assertSessionHas('success');

        $newSchool = School::where('npsn', '20262128')->first();
        $this->assertNotNull($newSchool);
        $this->assertEquals('SMK Negeri 10 Garut', $newSchool->name);

        // Verify school settings auto-created
        $this->assertEquals('06:00:00', $newSchool->getSetting('checkin_start'));
        $this->assertEquals('60', $newSchool->getSetting('weight_dudi'));

        // Verify admin user created
        $adminUser = User::where('email', 'admin@smknegeri10garut.sch.id')->first();
        $this->assertNotNull($adminUser);
        $this->assertEquals('admin-sekolah', $adminUser->role);

        // Verify subscription is trial / demo
        $sub = $newSchool->activeSubscription();
        $this->assertNotNull($sub);
        $this->assertEquals('trial', $sub->status);
        $this->assertEquals(0, (float) $sub->price_paid);
        $this->assertEquals(14, Carbon::today()->diffInDays($sub->end_date));

        // Verify trial invoice created
        $invoice = Invoice::where('school_id', $newSchool->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals('Trial Evaluasi', $invoice->payment_method);
    }

    /**
     * 4. Audit School Active/Inactive Toggle
     */
    public function test_superadmin_can_toggle_school_status(): void
    {
        $this->assertTrue($this->school1->is_active);

        // Deactivate
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.schools.toggle', $this->school1));
        $response->assertSessionHas('success');
        $this->assertFalse($this->school1->fresh()->is_active);

        // Reactivate
        $response2 = $this->actingAs($this->superAdmin)->post(route('superadmin.schools.toggle', $this->school1));
        $response2->assertSessionHas('success');
        $this->assertTrue($this->school1->fresh()->is_active);
    }

    /**
     * 5. Audit Extending & Upgrading Demo School to Paid Plan
     */
    public function test_superadmin_can_extend_and_upgrade_demo_school_to_paid_plan(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.schools.extend', $this->school1), [
            'months' => 12,
            'price' => 6000000,
            'new_quota' => 300,
            'plan_id' => $this->proPlan->id,
            'notes' => 'Upgrade lisensi dari Demo ke Paket Pro Tahunan',
        ]);

        $response->assertSessionHas('success');

        $activeSub = $this->school1->activeSubscription();
        $this->assertEquals('active', $activeSub->status);
        $this->assertEquals($this->proPlan->id, $activeSub->plan_id);
        $this->assertEquals($this->proPlan->name, $activeSub->plan_name);
        $this->assertEquals(300, $activeSub->student_quota);
        $this->assertEquals(6000000, (float) $activeSub->price_paid);

        // Verify paid invoice was generated
        $paidInvoice = Invoice::where('school_id', $this->school1->id)
            ->where('amount', 6000000)
            ->first();
        $this->assertNotNull($paidInvoice);
        $this->assertEquals('paid', $paidInvoice->status);
    }

    /**
     * 6. Audit SuperAdmin Impersonation into School Admin
     */
    public function test_superadmin_can_impersonate_school_admin_and_return(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.schools.impersonate', $this->school1));
        $response->assertRedirect(route('adminsekolah.dashboard'));

        $this->assertEquals('admin-sekolah', auth()->user()->role);
        $this->assertEquals('super-admin', session('impersonator_original_role'));

        // Leave impersonation
        $leaveResponse = $this->actingAs(auth()->user())->post(route('impersonate.leave'));
        $leaveResponse->assertRedirect(route('superadmin.dashboard'));
        $this->assertEquals('super-admin', auth()->user()->role);
        $this->assertFalse(session()->has('impersonator_original_role'));
    }

    /**
     * 7. Audit SaaS Invoices Creation & Verification
     */
    public function test_superadmin_invoices_workflow(): void
    {
        // 1. View invoices page
        $this->actingAs($this->superAdmin)->get(route('superadmin.invoices'))
            ->assertStatus(200)
            ->assertSee('Tagihan & Invoice', false);

        // 2. Create manual invoice
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.invoices.store'), [
            'school_id' => $this->school2->id,
            'title' => 'Tagihan Add-on WhatsApp Gateway',
            'amount' => 500000,
            'due_date' => Carbon::today()->addDays(7)->toDateString(),
            'notes' => 'Aktivasi integrasi WhatsApp',
        ]);
        $response->assertSessionHas('success');

        $invoice = Invoice::where('title', 'Tagihan Add-on WhatsApp Gateway')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('unpaid', $invoice->status);

        // 3. Mark invoice paid
        $payResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.invoices.verify', $invoice));
        $payResponse->assertSessionHas('success');
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    /**
     * 8. Audit Plans CRUD & Core Trial Plan Deletion Protection
     */
    public function test_superadmin_plans_crud_and_trial_plan_protection(): void
    {
        // 1. View plans page
        $this->actingAs($this->superAdmin)->get(route('superadmin.plans'))
            ->assertStatus(200)
            ->assertSee('Katalog Paket Sewa Platform');

        // 2. Create new plan
        $createResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.plans.store'), [
            'code' => 'custom-trial-30d',
            'name' => 'Demo Khusus 30 Hari',
            'price' => 0,
            'billing_period' => 'monthly',
            'max_students' => 80,
            'max_storage_mb' => 3072,
            'description' => 'Demo khusus pameran',
        ]);
        $createResponse->assertSessionHas('success');

        $customPlan = SubscriptionPlan::where('code', 'custom-trial-30d')->first();
        $this->assertNotNull($customPlan);

        // 3. Update plan
        $updateResponse = $this->actingAs($this->superAdmin)->put(route('superadmin.plans.update', $customPlan), [
            'name' => 'Demo Khusus 30 Hari (Updated)',
            'price' => 0,
            'max_students' => 100,
            'max_storage_mb' => 4096,
            'description' => 'Updated demo',
        ]);
        $updateResponse->assertSessionHas('success');
        $this->assertEquals(100, $customPlan->fresh()->max_students);

        // 4. Try to delete the core trial plan -> should be blocked!
        $deleteTrialResponse = $this->actingAs($this->superAdmin)->delete(route('superadmin.plans.destroy', $this->trialPlan));
        $deleteTrialResponse->assertSessionHas('error');
        $this->assertDatabaseHas('subscription_plans', ['code' => 'trial-14d']);

        // 5. Delete the custom unused plan -> should succeed
        $deleteCustomResponse = $this->actingAs($this->superAdmin)->delete(route('superadmin.plans.destroy', $customPlan));
        $deleteCustomResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('subscription_plans', ['code' => 'custom-trial-30d']);
    }

    /**
     * 9. Audit Platform Settings & Branding Update
     */
    public function test_superadmin_can_update_platform_branding_and_logo(): void
    {
        Storage::fake('public');

        $logoFile = UploadedFile::fake()->image('platform_logo.png', 180, 60);

        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.settings.update'), [
            'platform_name' => 'VokaTrack Enterprise',
            'platform_tagline' => 'Solusi PKL SMK Modern Berbasis Cloud',
            'app_description' => 'Sistem Informasi PKL Multi-Tenancy SMK',
            'system_logo' => $logoFile,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('VokaTrack Enterprise', PlatformSetting::get('platform_name'));
        $this->assertEquals('Solusi PKL SMK Modern Berbasis Cloud', PlatformSetting::get('platform_tagline'));
        $this->assertNotNull(PlatformSetting::get('system_logo'));
    }

    /**
     * 10. Audit Quota Checking on Demo Package
     */
    public function test_demo_package_quota_enforcement(): void
    {
        $subService = app(SubscriptionService::class);

        // School 1 has quota 50 and 0 students
        $canAdd = $subService->canAddStudent($this->school1, 10);
        $this->assertTrue($canAdd['allowed']);
        $this->assertEquals(50, $canAdd['remaining']);

        // Exceeding quota: try to add 60 students to 50 quota
        $cannotAdd = $subService->canAddStudent($this->school1, 60);
        $this->assertFalse($cannotAdd['allowed']);
        $this->assertStringContainsString('Kuota siswa terlisensi', $cannotAdd['message']);
    }
}
