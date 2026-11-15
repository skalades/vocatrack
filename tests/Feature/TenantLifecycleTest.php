<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\TenantLifecycleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TenantLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $schoolAdmin;
    protected School $school;
    protected SubscriptionPlan $trialPlan;
    protected SubscriptionPlan $proPlan;
    protected SubscriptionPlan $semesterPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'name' => 'Super', 'email' => 'sa@vokatrack.id', 'password' => 'secret-123',
            'role' => 'super-admin', 'is_active' => true,
        ]);

        $features = ['attendance' => true, 'journal' => true, 'assessment' => true];
        $this->trialPlan = SubscriptionPlan::create([
            'code' => 'trial-14d', 'name' => 'Trial', 'price' => 0, 'billing_period' => 'monthly',
            'max_students' => 50, 'max_storage_mb' => 1024, 'features' => $features, 'is_active' => true,
        ]);
        $this->proPlan = SubscriptionPlan::create([
            'code' => 'pro-yearly', 'name' => 'Pro Tahunan', 'price' => 6000000, 'billing_period' => 'yearly',
            'max_students' => 300, 'max_storage_mb' => 20480, 'features' => $features, 'is_active' => true,
        ]);
        $this->semesterPlan = SubscriptionPlan::create([
            'code' => 'basic-semester', 'name' => 'Basic Semester', 'price' => 1800000, 'billing_period' => 'semester',
            'max_students' => 120, 'max_storage_mb' => 5120, 'features' => $features, 'is_active' => true,
        ]);

        $this->school = School::create([
            'npsn' => '11112222', 'name' => 'SMK Siklus', 'address' => 'Jl. Uji', 'phone' => '0',
            'email' => 'info@siklus.sch.id', 'is_active' => true,
        ]);
        $this->schoolAdmin = User::create([
            'school_id' => $this->school->id, 'name' => 'Admin Siklus', 'email' => 'admin@siklus.sch.id',
            'password' => 'secret-123', 'role' => 'admin-sekolah', 'is_active' => true,
        ]);
    }

    protected function lifecycle(): TenantLifecycleService
    {
        return app(TenantLifecycleService::class);
    }

    public function test_stage_is_computed_from_period_and_activation_flag(): void
    {
        $this->assertSame('no_plan', $this->lifecycle()->stage($this->school));

        $sub = app(SubscriptionService::class)->subscribeSchool($this->school, $this->proPlan, Carbon::today()->subMonths(11));
        $this->assertSame('expiring', $this->lifecycle()->stage($this->school->fresh()));

        $sub->update(['end_date' => Carbon::today()->addDays(90)]);
        $this->assertSame('active', $this->lifecycle()->stage($this->school->fresh()));

        $sub->update(['end_date' => Carbon::today()->subDays(3)]);
        $this->assertSame('grace', $this->lifecycle()->stage($this->school->fresh()));

        $sub->update(['end_date' => Carbon::today()->subDays(8)]);
        $this->assertSame('expired', $this->lifecycle()->stage($this->school->fresh()));

        $this->school->update(['is_active' => false]);
        $this->assertSame('suspended', $this->lifecycle()->stage($this->school->fresh()));
    }

    public function test_paid_renewal_creates_new_period_and_keeps_history(): void
    {
        $first = app(SubscriptionService::class)->subscribeSchool($this->school, $this->proPlan, Carbon::today()->subMonths(11));

        $this->actingAs($this->superAdmin)->post(route('superadmin.schools.extend', $this->school), [
            'months' => 12, 'price' => 6000000, 'payment_status' => 'paid',
        ])->assertSessionHas('success');

        $periods = SchoolSubscription::where('school_id', $this->school->id)->orderBy('start_date')->get();
        $this->assertCount(2, $periods);
        $this->assertEquals(6000000, (float) $periods[0]->price_paid, 'Nilai periode lama tidak ikut menumpuk');
        $this->assertTrue($periods[1]->start_date->isSameDay($first->end_date), 'Periode baru menyambung periode lama');
        $this->assertTrue($periods[1]->end_date->isSameDay($first->end_date->copy()->addMonths(12)));
        $this->assertSame('active', $this->lifecycle()->stage($this->school->fresh()));

        // MRR dihitung dari harga periode berjalan / 12 bulan, bukan total kumulatif.
        $metrics = app(SubscriptionService::class)->getSaaSMetrics();
        $this->assertEquals(500000, $metrics['mrr_estimate']);
        $this->assertEquals(6000000, $metrics['arr_estimate']);
    }

    public function test_unpaid_renewal_waits_for_payment_then_activates_period(): void
    {
        $first = app(SubscriptionService::class)->subscribeSchool($this->school, $this->proPlan, Carbon::today()->subMonths(11));

        $this->actingAs($this->superAdmin)->post(route('superadmin.schools.extend', $this->school), [
            'months' => 12, 'price' => 6000000, 'payment_status' => 'unpaid',
        ])->assertSessionHas('success');

        $this->assertSame(1, SchoolSubscription::where('school_id', $this->school->id)->count(), 'Belum ada periode baru sebelum lunas');
        $invoice = Invoice::where('school_id', $this->school->id)->where('status', 'unpaid')->firstOrFail();
        $this->assertSame('renewal', $invoice->type);

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->schoolAdmin->id]);

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.invoices.verify', $invoice), ['payment_method' => 'Transfer BNI'])
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertNotNull($invoice->applied_at);
        $this->assertSame('Transfer BNI', $invoice->payment_method);

        $current = $this->school->fresh()->activeSubscription();
        $this->assertNotSame($first->id, $current->id);
        $this->assertTrue($current->end_date->isSameDay($first->end_date->copy()->addMonths(12)));

        // Verifikasi ulang ditolak dan tidak membuat periode ganda.
        $this->actingAs($this->superAdmin)->post(route('superadmin.invoices.verify', $invoice))->assertSessionHas('error');
        $this->assertSame(2, SchoolSubscription::where('school_id', $this->school->id)->count());
    }

    public function test_onboarding_paid_plan_unpaid_gets_trial_then_converts_on_payment(): void
    {
        $this->actingAs($this->superAdmin)->post(route('superadmin.schools.store'), [
            'npsn' => '33334444', 'name' => 'SMK Baru', 'address' => '-', 'phone' => '0', 'email' => 'x@baru.sch.id',
            'admin_name' => 'Admin Baru', 'admin_email' => 'admin@baru.sch.id',
            'plan_id' => $this->semesterPlan->id, 'payment_status' => 'unpaid',
        ])->assertSessionHas('success');

        $school = School::where('npsn', '33334444')->firstOrFail();
        $this->assertSame('trial', $this->lifecycle()->stage($school));

        $invoice = Invoice::where('school_id', $school->id)->firstOrFail();
        $this->assertSame('activation', $invoice->type);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame(6, $invoice->period_months);

        $this->lifecycle()->recordPayment($invoice);

        $school->refresh();
        $current = $school->activeSubscription();
        $this->assertSame('active', $current->status);
        $this->assertSame($this->semesterPlan->id, $current->plan_id);
        $this->assertTrue($current->start_date->isToday(), 'Trial dikonversi, periode berbayar mulai hari ini');
        $this->assertTrue($current->end_date->isSameDay(Carbon::today()->addMonths(6)));
        $this->assertSame('converted', SchoolSubscription::where('school_id', $school->id)->where('status', 'converted')->value('status'));
    }

    public function test_daily_evaluation_issues_single_renewal_invoice_and_notifies(): void
    {
        app(SubscriptionService::class)->subscribeSchool($this->school, $this->proPlan, Carbon::today()->subMonths(12)->addDays(20));

        Artisan::call('saas:evaluate-subscriptions');
        Artisan::call('saas:evaluate-subscriptions');

        $renewals = Invoice::where('school_id', $this->school->id)->where('type', 'renewal')->get();
        $this->assertCount(1, $renewals, 'Tagihan perpanjangan otomatis tidak boleh ganda');
        $this->assertSame('unpaid', $renewals[0]->status);
        $this->assertEquals(6000000, (float) $renewals[0]->amount);
        $this->assertSame(12, $renewals[0]->period_months);

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->schoolAdmin->id]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->superAdmin->id]);
    }

    public function test_new_renewal_supersedes_older_open_period_invoice(): void
    {
        app(SubscriptionService::class)->subscribeSchool($this->school, $this->proPlan, Carbon::today()->subMonths(11));

        $old = $this->lifecycle()->renew($this->school, $this->proPlan, 12, 6000000, false)['invoice'];
        $new = $this->lifecycle()->renew($this->school, $this->semesterPlan, 6, 1800000, false)['invoice'];

        $this->assertSame('cancelled', $old->fresh()->status);
        $this->assertSame('unpaid', $new->fresh()->status);
        $this->assertSame('upgrade', $new->type);
    }

    public function test_school_detail_page_renders_every_tab(): void
    {
        app(SubscriptionService::class)->subscribeSchool($this->school, $this->proPlan, Carbon::today()->subMonths(11));
        $this->lifecycle()->renew($this->school, $this->proPlan, 12, 6000000, false);

        foreach (['ringkasan', 'langganan', 'tagihan', 'pengguna', 'aktivitas', 'pengaturan'] as $tab) {
            $this->actingAs($this->superAdmin)
                ->get(route('superadmin.schools.show', [$this->school, 'tab' => $tab]))
                ->assertOk()
                ->assertSee('SMK Siklus')
                ->assertSee('Akan Habis')
                ->assertSee('Tandai Lunas');
        }
    }
}
