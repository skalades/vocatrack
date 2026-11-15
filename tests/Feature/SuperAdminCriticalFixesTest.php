<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\PlatformSetting;
use App\Models\School;
use App\Models\SchoolRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\BackupService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regresi untuk temuan audit Super Admin tingkat Kritis & Tinggi.
 */
class SuperAdminCriticalFixesTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected SubscriptionPlan $trialPlan;
    protected SubscriptionPlan $proPlan;
    protected SubscriptionPlan $basicPlan;
    protected School $school;
    protected User $schoolAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@vokatrack.id',
            'password' => 'secret-super-1',
            'role' => 'super-admin',
            'is_active' => true,
        ]);

        $features = ['attendance' => true, 'journal' => true, 'assessment' => true];
        $this->trialPlan = SubscriptionPlan::create([
            'code' => 'trial-14d', 'name' => 'Trial (7 Hari)', 'price' => 0, 'billing_period' => 'monthly',
            'max_students' => 50, 'max_storage_mb' => 1024, 'features' => $features, 'is_active' => true,
        ]);
        $this->basicPlan = SubscriptionPlan::create([
            'code' => 'basic-yearly', 'name' => 'Paket Basic', 'price' => 2000000, 'billing_period' => 'yearly',
            'max_students' => 100, 'max_storage_mb' => 5120, 'features' => $features, 'is_active' => true,
        ]);
        $this->proPlan = SubscriptionPlan::create([
            'code' => 'pro-yearly', 'name' => 'Paket Pro', 'price' => 6000000, 'billing_period' => 'yearly',
            'max_students' => 300, 'max_storage_mb' => 20480, 'features' => $features, 'is_active' => true,
        ]);

        $this->school = School::create([
            'npsn' => '20209201', 'name' => 'SMK Negeri 1 Garut', 'address' => 'Jl. Cimanuk',
            'phone' => '0262', 'email' => 'info@smkn1garut.sch.id', 'is_active' => true,
        ]);
        $this->schoolAdmin = User::create([
            'school_id' => $this->school->id, 'name' => 'Admin SMKN 1', 'email' => 'admin@smkn1garut.sch.id',
            'password' => 'password-lama', 'role' => 'admin-sekolah', 'is_active' => true,
        ]);
        app(SubscriptionService::class)->subscribeSchool($this->school, $this->basicPlan, Carbon::today());
    }

    // ---------- K1/K2: kredensial sementara ----------

    public function test_new_school_admin_gets_random_password_and_must_change_it(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.schools.store'), [
            'npsn' => '20262128', 'name' => 'SMK Negeri 10 Garut', 'address' => 'Jl. Garut', 'phone' => '0262',
            'email' => 'info@smkn10.sch.id', 'admin_name' => 'Admin 10', 'admin_email' => 'admin@smkn10.sch.id',
            'plan_id' => $this->trialPlan->id,
        ]);

        $response->assertSessionHas('temporary_credential');
        $credential = session('temporary_credential');
        $this->assertNotSame('password', $credential['password']);
        $this->assertGreaterThanOrEqual(12, strlen($credential['password']));
        $this->assertStringNotContainsString($credential['password'], session('success'));

        $admin = User::where('email', 'admin@smkn10.sch.id')->first();
        $this->assertTrue($admin->must_change_password);
        $this->assertTrue(Hash::check($credential['password'], $admin->password));
        $this->assertFalse(Hash::check('password', $admin->password));
    }

    public function test_approved_demo_request_admin_gets_random_password(): void
    {
        $request = SchoolRequest::create([
            'school_name' => 'SMK Demo', 'npsn' => '30303030', 'contact_name' => 'Bu Demo',
            'email' => 'demo@smkdemo.sch.id', 'phone' => '0812', 'status' => 'pending',
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.school-requests.approve', $request), ['plan_id' => $this->trialPlan->id])
            ->assertSessionHas('temporary_credential');

        $admin = User::where('email', 'demo@smkdemo.sch.id')->first();
        $this->assertTrue($admin->must_change_password);
        $this->assertFalse(Hash::check('password', $admin->password));

        // Approve ulang tidak boleh diproses dua kali.
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.school-requests.approve', $request), ['plan_id' => $this->trialPlan->id])
            ->assertSessionHas('error');
    }

    public function test_reset_admin_password_validates_and_generates_random_password(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.schools.reset-admin-password', $this->school), ['new_password' => '123'])
            ->assertSessionHasErrors('new_password');

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.schools.reset-admin-password', $this->school), ['new_password' => ''])
            ->assertSessionHas('temporary_credential');

        $credential = session('temporary_credential');
        $admin = $this->schoolAdmin->fresh();
        $this->assertTrue(Hash::check($credential['password'], $admin->password));
        $this->assertNotSame('password', $credential['password']);
        $this->assertTrue($admin->must_change_password);
    }

    public function test_user_with_temporary_password_cannot_write_until_changed(): void
    {
        $this->schoolAdmin->update(['must_change_password' => true]);

        // Halaman tetap terbuka (modal ganti sandi tampil), tetapi aksi tulis diblokir.
        $this->actingAs($this->schoolAdmin)->get(route('adminsekolah.dashboard'))
            ->assertOk()
            ->assertSee('kata sandi sementara', false);

        $this->actingAs($this->schoolAdmin)
            ->from(route('adminsekolah.dashboard'))
            ->post(route('adminsekolah.majors.store'), ['code' => 'TKJ', 'name' => 'Teknik Komputer'])
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('majors', ['code' => 'TKJ', 'school_id' => $this->school->id]);

        $this->actingAs($this->schoolAdmin)->post(route('profile.change-password'), [
            'current_password' => 'password-lama',
            'password' => 'sandi-baru-aman',
            'password_confirmation' => 'sandi-baru-aman',
        ])->assertSessionHas('success');

        $this->assertFalse($this->schoolAdmin->fresh()->must_change_password);
    }

    // ---------- K3: restore aman ----------

    public function test_restore_preserves_audit_log_and_current_super_admin(): void
    {
        $service = app(BackupService::class);
        $backup = $service->createDatabaseBackup('test_restore');

        // Setelah snapshot: ada log baru dan super admin mengganti kata sandi.
        ActivityLog::create(['action' => 'after_snapshot', 'module' => 'security', 'description' => 'Log setelah snapshot']);
        $this->superAdmin->update(['password' => 'secret-super-2']);

        try {
            $result = $service->restoreDatabaseBackup($backup['filename']);

            $this->assertDatabaseHas('activity_logs', ['action' => 'after_snapshot']);
            $superAdmin = User::where('email', 'superadmin@vokatrack.id')->first();
            $this->assertNotNull($superAdmin);
            $this->assertTrue(Hash::check('secret-super-2', $superAdmin->password));
            $this->assertDatabaseHas('schools', ['npsn' => '20209201']);
        } finally {
            File::delete(storage_path('app/backups/' . $backup['filename']));
            if (isset($result['safety_snapshot'])) {
                File::delete(storage_path('app/backups/' . $result['safety_snapshot']));
            }
        }
    }

    // ---------- K4/K5: rahasia & upload logo ----------

    public function test_integration_secrets_are_encrypted_and_never_rendered(): void
    {
        $this->actingAs($this->superAdmin)->post(route('superadmin.settings.update'), [
            'platform_name' => 'VokaTrack',
            'mail_host' => 'smtp.example.com', 'mail_port' => 587, 'mail_password' => 'smtp-rahasia-123',
            'wa_provider' => 'fonnte', 'wa_api_token' => 'wa-token-rahasia-xyz',
        ])->assertSessionHas('success');

        $rawToken = DB::table('platform_settings')->where('key', 'wa_api_token')->value('value');
        $rawMail = DB::table('platform_settings')->where('key', 'mail_password')->value('value');
        $this->assertNotSame('wa-token-rahasia-xyz', $rawToken);
        $this->assertNotSame('smtp-rahasia-123', $rawMail);
        $this->assertSame('wa-token-rahasia-xyz', PlatformSetting::getSecret('wa_api_token'));
        $this->assertSame('smtp-rahasia-123', PlatformSetting::getSecret('mail_password'));

        $this->actingAs($this->superAdmin)->get(route('superadmin.settings'))
            ->assertOk()
            ->assertDontSee('wa-token-rahasia-xyz')
            ->assertDontSee('smtp-rahasia-123');
    }

    public function test_svg_logo_upload_is_rejected(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.settings.update'), ['platform_name' => 'VokaTrack', 'system_logo' => $svg])
            ->assertSessionHasErrors('system_logo');
    }

    // ---------- T6-T8: langganan & invoice ----------

    public function test_plan_upgrade_applies_even_with_custom_storage(): void
    {
        $this->actingAs($this->superAdmin)->post(route('superadmin.schools.extend', $this->school), [
            'months' => 12, 'price' => 6000000, 'plan_id' => $this->proPlan->id, 'custom_storage_mb' => 40960,
        ])->assertSessionHas('success');

        $sub = $this->school->activeSubscription();
        $this->assertSame($this->proPlan->id, $sub->plan_id);
        $this->assertSame($this->proPlan->name, $sub->plan_name);
        $this->assertSame(40960, (int) $sub->storage_quota_mb);
        $this->assertSame(300, (int) $sub->student_quota, 'Kuota siswa mengikuti paket baru bila tidak diisi manual');
    }

    public function test_extension_can_issue_unpaid_invoice_with_custom_method(): void
    {
        $this->actingAs($this->superAdmin)->post(route('superadmin.schools.extend', $this->school), [
            'months' => 6, 'price' => 1500000, 'payment_status' => 'unpaid', 'payment_method' => 'Dana BOS',
        ])->assertSessionHas('success');

        $invoice = Invoice::where('school_id', $this->school->id)->where('amount', 1500000)->first();
        $this->assertSame('unpaid', $invoice->status);
        $this->assertNull($invoice->paid_at);
        $this->assertSame('Dana BOS', $invoice->payment_method);

        // Pendapatan hanya dihitung dari invoice lunas.
        $metrics = app(SubscriptionService::class)->getSaaSMetrics();
        $this->assertEquals(2000000, $metrics['total_revenue']);
    }

    public function test_extending_school_without_subscription_creates_single_invoice(): void
    {
        $school = School::create([
            'npsn' => '40404040', 'name' => 'SMK Tanpa Langganan', 'address' => '-', 'phone' => '0',
            'email' => 'x@y.sch.id', 'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin)->post(route('superadmin.schools.extend', $school), [
            'months' => 12, 'price' => 6000000, 'plan_id' => $this->proPlan->id,
        ])->assertSessionHas('success');

        $this->assertSame(1, Invoice::where('school_id', $school->id)->count());
        $this->assertEquals(6000000, (float) Invoice::where('school_id', $school->id)->sum('amount'));
        $sub = $school->activeSubscription();
        $this->assertTrue($sub->end_date->isSameDay(Carbon::today()->addMonths(12)));
    }

    // ---------- T9: verifikasi invoice ----------

    public function test_cancelled_or_paid_invoice_cannot_be_verified_again(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-1', 'school_id' => $this->school->id, 'title' => 'Add-on',
            'amount' => 500000, 'due_date' => Carbon::today()->addDays(7), 'status' => 'cancelled',
        ]);

        $this->actingAs($this->superAdmin)->post(route('superadmin.invoices.verify', $invoice))->assertSessionHas('error');
        $this->assertSame('cancelled', $invoice->fresh()->status);

        $invoice->update(['status' => 'paid', 'paid_at' => Carbon::yesterday()]);
        $paidAt = $invoice->fresh()->paid_at;
        $this->actingAs($this->superAdmin)->post(route('superadmin.invoices.verify', $invoice))->assertSessionHas('error');
        $this->assertTrue($invoice->fresh()->paid_at->equalTo($paidAt));
    }

    // ---------- T10: hapus tenant ----------

    public function test_destroy_school_requires_inactive_and_npsn_confirmation_and_cleans_up(): void
    {
        DB::table('majors')->insert(['school_id' => $this->school->id, 'code' => 'TKJ', 'name' => 'TKJ', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.schools.destroy', $this->school), ['confirm_npsn' => '20209201'])
            ->assertSessionHas('error');
        $this->assertDatabaseHas('schools', ['id' => $this->school->id]);

        $this->school->update(['is_active' => false]);

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.schools.destroy', $this->school), ['confirm_npsn' => 'salah'])
            ->assertSessionHas('error');
        $this->assertDatabaseHas('schools', ['id' => $this->school->id]);

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.schools.destroy', $this->school), ['confirm_npsn' => '20209201'])
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('schools', ['id' => $this->school->id]);
        $this->assertDatabaseMissing('majors', ['school_id' => $this->school->id]);
        $this->assertDatabaseMissing('users', ['email' => 'admin@smkn1garut.sch.id']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'school_delete']);
    }

    // ---------- T11: impersonasi ----------

    public function test_nested_impersonation_is_blocked(): void
    {
        $guru = User::create([
            'school_id' => $this->school->id, 'name' => 'Guru', 'email' => 'guru@smkn1garut.sch.id',
            'password' => 'password-guru', 'role' => 'guru-pembimbing', 'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin)->post(route('superadmin.schools.impersonate', $this->school));
        $this->assertSame('admin-sekolah', auth()->user()->role);

        $this->post(route('impersonate.start', $guru))->assertSessionHasErrors('impersonate');
        $this->assertSame('admin-sekolah', auth()->user()->role);
        $this->assertSame($this->superAdmin->id, session('impersonator_id'));

        $this->post(route('impersonate.leave'))->assertRedirect(route('superadmin.dashboard'));
        $this->assertSame('super-admin', auth()->user()->role);
    }

    // ---------- T12: kunci pemeliharaan ----------

    public function test_maintenance_lock_blocks_tenant_writes_but_not_reads(): void
    {
        PlatformSetting::set('maintenance_lock', '1');

        $this->actingAs($this->schoolAdmin)->get(route('adminsekolah.dashboard'))->assertOk();

        $this->actingAs($this->schoolAdmin)
            ->from(route('adminsekolah.dashboard'))
            ->post(route('adminsekolah.majors.store'), ['code' => 'TKJ', 'name' => 'Teknik Komputer'])
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('majors', ['code' => 'TKJ', 'school_id' => $this->school->id]);

        // Banner siaran saja (maintenance_active) tidak mengunci penulisan.
        PlatformSetting::set('maintenance_lock', '0');
        PlatformSetting::set('maintenance_active', '1');
        $this->actingAs($this->schoolAdmin)
            ->post(route('adminsekolah.majors.store'), ['code' => 'TKJ', 'name' => 'Teknik Komputer']);
        $this->assertDatabaseHas('majors', ['code' => 'TKJ', 'school_id' => $this->school->id]);
    }
}
