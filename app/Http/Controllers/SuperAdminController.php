<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PlatformSetting;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\SchoolSubscription;
use App\Models\Student;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ImpersonationService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function dashboard()
    {
        $metrics = $this->subscriptionService->getSaaSMetrics();

        // Schools with relations
        $recentSchools = School::with(['subscriptions' => function ($q) {
                $q->latest('end_date');
            }, 'users'])
            ->withCount(['users', 'classes', 'companies'])
            ->latest()
            ->take(6)
            ->get();

        // Calculate student count for each school
        foreach ($recentSchools as $sch) {
            $sch->students_count = Student::whereHas('class', function ($q) use ($sch) {
                $q->where('school_id', $sch->id);
            })->count();
        }

        // Schools expiring within 30 days
        $thirtyDays = Carbon::today()->addDays(30);
        $expiringSubscriptions = SchoolSubscription::with('school')
            ->where('end_date', '>=', Carbon::today())
            ->where('end_date', '<=', $thirtyDays)
            ->where('status', '!=', 'suspended')
            ->get();

        // Recent Invoices
        $recentInvoices = Invoice::with('school')
            ->latest()
            ->take(5)
            ->get();

        // Available Plans for Quick Actions / Modals
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return view('superadmin.dashboard', compact(
            'metrics',
            'recentSchools',
            'expiringSubscriptions',
            'recentInvoices',
            'plans'
        ));
    }

    public function schools()
    {
        $schools = School::with(['subscriptions' => function ($q) {
                $q->latest('end_date');
            }])
            ->withCount(['users', 'classes', 'companies'])
            ->latest()
            ->paginate(10);

        foreach ($schools as $sch) {
            $sch->students_count = Student::whereHas('class', function ($q) use ($sch) {
                $q->where('school_id', $sch->id);
            })->count();
        }

        $plans = SubscriptionPlan::where('is_active', true)->get();

        return view('superadmin.schools', compact('schools', 'plans'));
    }

    public function storeSchool(Request $request)
    {
        $request->validate([
            'npsn' => ['required', 'string', 'size:8', 'unique:schools,npsn'],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:100'],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'unique:users,email'],
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'custom_quota' => ['nullable', 'integer', 'min:10'],
        ]);

        $school = DB::transaction(function () use ($request) {
            $school = School::create([
                'npsn' => $request->npsn,
                'name' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
                'is_active' => true,
            ]);

            // Default School Settings
            $defaultSettings = [
                'checkin_start' => '06:00:00',
                'checkin_late' => '08:00:00',
                'checkin_alfa' => '11:00:00',
                'checkout_start' => '16:00:00',
                'journal_backdate_limit' => '3',
                'weight_dudi' => '60',
                'weight_guru' => '40',
                'kop_header_line1' => 'PEMERINTAH PROVINSI',
                'kop_header_line2' => 'DINAS PENDIDIKAN',
                'kop_header_line3' => strtoupper($school->name),
                'kepsek_name' => 'Kepala Sekolah',
                'kepsek_nip' => '-',
            ];

            foreach ($defaultSettings as $k => $v) {
                SchoolSetting::create([
                    'school_id' => $school->id,
                    'key' => $k,
                    'value' => $v,
                ]);
            }

            // Auto-create initial School Admin
            User::create([
                'school_id' => $school->id,
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make('password'),
                'role' => 'admin-sekolah',
                'is_active' => true,
            ]);

            // Activate Subscription Plan
            $plan = SubscriptionPlan::findOrFail($request->plan_id);
            $customQuota = $request->custom_quota ?: null;
            $status = $plan->price > 0 ? 'active' : 'trial';

            $this->subscriptionService->subscribeSchool(
                $school,
                $plan,
                Carbon::today(),
                $customQuota,
                null,
                $status,
                "Aktivasi awal tenant sekolah ({$plan->name})"
            );

            return $school;
        });

        return back()->with('success', "Sekolah {$school->name} dan paket sewa berhasil didaftarkan. Akun admin dibuat dengan password: password");
    }

    public function extendSubscription(Request $request, School $school)
    {
        $request->validate([
            'months' => ['required', 'integer', 'in:1,3,6,12,24'],
            'price' => ['required', 'numeric', 'min:0'],
            'new_quota' => ['nullable', 'integer', 'min:10'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $activeSub = $school->activeSubscription();
        if (!$activeSub) {
            $defaultPlan = SubscriptionPlan::where('code', 'pro-yearly')->first() ?? SubscriptionPlan::first();
            $activeSub = $this->subscriptionService->subscribeSchool($school, $defaultPlan);
        }

        $this->subscriptionService->extendSubscription(
            $activeSub,
            (int) $request->months,
            (float) $request->price,
            $request->new_quota ? (int) $request->new_quota : null,
            $request->notes
        );

        return back()->with('success', "Masa sewa sekolah {$school->name} berhasil diperpanjang +{$request->months} bulan.");
    }

    public function toggleSchool(School $school)
    {
        $school->update(['is_active' => !$school->is_active]);

        $status = $school->is_active ? 'diaktifkan' : 'dinonaktifkan (seluruh sesi user diblokir)';
        return back()->with('success', "Status sekolah {$school->name} berhasil {$status}.");
    }

    public function impersonateSchool(School $school, ImpersonationService $service)
    {
        $adminUser = User::where('school_id', $school->id)
            ->where('role', 'admin-sekolah')
            ->where('is_active', true)
            ->first();

        if (!$adminUser) {
            // Find any active user in this school
            $adminUser = User::where('school_id', $school->id)->where('is_active', true)->first();
        }

        if (!$adminUser) {
            return back()->with('error', "Tidak ditemukan akun administrator aktif untuk sekolah {$school->name}.");
        }

        $service->startImpersonation(Auth::user(), $adminUser);

        return redirect()->route('adminsekolah.dashboard')->with('success', "Mode Impersonasi Super Admin Aktif: Anda saat ini mengelola tenant {$school->name} sebagai {$adminUser->name}.");
    }

    public function invoices(Request $request)
    {
        $query = Invoice::with(['school', 'subscription'])->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('school', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->paginate(15);
        $schools = School::where('is_active', true)->orderBy('name')->get();

        return view('superadmin.invoices', compact('invoices', 'schools'));
    }

    public function storeInvoice(Request $request)
    {
        $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $school = School::findOrFail($request->school_id);
        $invoiceNumber = 'INV-' . date('Ym') . '-' . strtoupper(Str::random(5));

        Invoice::create([
            'invoice_number' => $invoiceNumber,
            'school_id' => $school->id,
            'subscription_id' => $school->activeSubscription()?->id,
            'title' => $request->title,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'status' => 'unpaid',
            'notes' => $request->notes,
        ]);

        return back()->with('success', "Invoice tagihan {$invoiceNumber} untuk {$school->name} berhasil diterbitkan.");
    }

    public function markInvoicePaid(Invoice $invoice)
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => Carbon::now(),
        ]);

        // If subscription is linked and was expired, mark subscription active
        if ($invoice->subscription && $invoice->subscription->status === 'expired') {
            $invoice->subscription->update(['status' => 'active']);
        }

        return back()->with('success', "Invoice {$invoice->invoice_number} berhasil ditandai LUNAS.");
    }

    public function plans()
    {
        if (SubscriptionPlan::count() === 0) {
            (new \Database\Seeders\SubscriptionPlanSeeder())->run();
        }

        $plans = SubscriptionPlan::withCount('subscriptions')->get();
        return view('superadmin.plans', compact('plans'));
    }

    public function storePlan(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:subscription_plans,code'],
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_period' => ['required', 'in:monthly,semester,yearly'],
            'max_students' => ['required', 'integer', 'min:1'],
            'max_storage_mb' => ['required', 'integer', 'min:500'],
            'is_popular' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $features = [
            'attendance' => true,
            'journal' => true,
            'assessment' => true,
            'certificate' => $request->boolean('feat_certificate', true),
            'whatsapp_gateway' => $request->boolean('feat_wa', false),
            'custom_domain' => $request->boolean('feat_domain', false),
        ];

        SubscriptionPlan::create([
            'code' => Str::slug($request->code),
            'name' => $request->name,
            'price' => $request->price,
            'billing_period' => $request->billing_period,
            'max_students' => $request->max_students,
            'max_storage_mb' => $request->max_storage_mb,
            'features' => $features,
            'is_popular' => $request->boolean('is_popular'),
            'is_active' => $request->boolean('is_active', true),
            'description' => $request->description,
        ]);

        return back()->with('success', "Paket sewa baru {$request->name} berhasil ditambahkan.");
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'max_students' => ['required', 'integer', 'min:1'],
            'max_storage_mb' => ['required', 'integer', 'min:500'],
            'is_popular' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $features = $plan->features ?? [];
        if ($request->has('feat_certificate')) {
            $features['certificate'] = $request->boolean('feat_certificate');
        }
        if ($request->has('feat_wa')) {
            $features['whatsapp_gateway'] = $request->boolean('feat_wa');
        }
        if ($request->has('feat_domain')) {
            $features['custom_domain'] = $request->boolean('feat_domain');
        }

        $plan->update([
            'name' => $request->name,
            'price' => $request->price,
            'max_students' => $request->max_students,
            'max_storage_mb' => $request->max_storage_mb,
            'features' => $features,
            'is_popular' => $request->boolean('is_popular'),
            'is_active' => $request->boolean('is_active'),
            'description' => $request->description,
        ]);

        return back()->with('success', "Paket {$plan->name} berhasil diperbarui.");
    }

    public function destroyPlan(SubscriptionPlan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            return back()->with('error', "Paket {$plan->name} tidak dapat dihapus karena masih digunakan oleh sekolah mitra.");
        }

        $plan->delete();
        return back()->with('success', "Paket {$plan->name} berhasil dihapus.");
    }

    public function settings()
    {
        $currentLogoUrl = PlatformSetting::getLogoUrl();
        $hasCustomLogo = (bool) PlatformSetting::get('system_logo');
        $platformName = PlatformSetting::get('platform_name', 'VokaTrack');
        $platformTagline = PlatformSetting::get('platform_tagline', 'Enterprise Cloud');
        $appDescription = PlatformSetting::get('app_description', 'Platform PKL SMK Terpadu Berbasis Multi-Tenancy Cloud');

        return view('superadmin.settings', compact(
            'currentLogoUrl',
            'hasCustomLogo',
            'platformName',
            'platformTagline',
            'appDescription'
        ));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'platform_name' => ['required', 'string', 'max:60'],
            'platform_tagline' => ['nullable', 'string', 'max:100'],
            'app_description' => ['nullable', 'string', 'max:255'],
            'system_logo' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'reset_logo' => ['nullable', 'boolean'],
        ]);

        PlatformSetting::set('platform_name', $request->platform_name);
        PlatformSetting::set('platform_tagline', $request->platform_tagline ?: 'Enterprise Cloud');
        PlatformSetting::set('app_description', $request->app_description ?: '');

        // Handle Logo Reset
        if ($request->boolean('reset_logo')) {
            $oldLogo = PlatformSetting::get('system_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            PlatformSetting::forget('system_logo');
        } elseif ($request->hasFile('system_logo')) {
            // Delete old custom logo if exists
            $oldLogo = PlatformSetting::get('system_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $file = $request->file('system_logo');
            $filename = 'system_logo_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('branding', $filename, 'public');

            PlatformSetting::set('system_logo', $path);
        }

        return back()->with('success', 'Pengaturan branding dan logo sistem berhasil diperbarui.');
    }
}
