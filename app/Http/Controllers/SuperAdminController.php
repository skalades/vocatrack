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
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // Schools expiring within 30 days (based on active subscription)
        $thirtyDays = Carbon::today()->addDays(30);
        $expiringSubscriptions = SchoolSubscription::with('school')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('school_subscriptions')
                    ->groupBy('school_id');
            })
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

    public function schools(Request $request)
    {
        $query = School::with([
            'subscriptions' => function ($q) {
                $q->latest('end_date');
            },
            'invoices' => function ($q) {
                $q->latest()->take(5);
            },
            'users' => function ($q) {
                $q->where('role', 'admin-sekolah');
            }
        ])
        ->withCount(['users', 'classes', 'companies']);

        // 1. Search Query (Server-side scalable search)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('npsn', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // 2. Status Filter
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'inactive') {
                $query->where('is_active', false);
            } else {
                $query->where('is_active', true);
                if ($request->status === 'trial') {
                    $query->whereHas('subscriptions', function ($sq) {
                        $sq->where('status', 'trial');
                    });
                } elseif ($request->status === 'active') {
                    $query->whereHas('subscriptions', function ($sq) {
                        $sq->where('status', 'active')->where('end_date', '>=', Carbon::today());
                    });
                } elseif ($request->status === 'expiring_soon') {
                    $thirtyDays = Carbon::today()->addDays(30);
                    $query->whereHas('subscriptions', function ($sq) use ($thirtyDays) {
                        $sq->where('end_date', '>=', Carbon::today())
                           ->where('end_date', '<=', $thirtyDays);
                    });
                } elseif ($request->status === 'expired') {
                    $query->whereHas('subscriptions', function ($sq) {
                        $sq->where('end_date', '<', Carbon::today());
                    });
                }
            }
        }

        $schools = $query->latest()->paginate(10)->withQueryString();

        foreach ($schools as $sch) {
            $sch->students_count = Student::whereHas('class', function ($q) use ($sch) {
                $q->where('school_id', $sch->id);
            })->count();
        }

        $plans = SubscriptionPlan::where('is_active', true)->get();

        // Platform-wide accurate KPI counts
        $totalCount = School::count();
        $activeCount = School::where('is_active', true)->count();
        $totalStudents = Student::count();
        $totalCompanies = \App\Models\Company::count();

        return view('superadmin.schools', compact(
            'schools', 
            'plans', 
            'totalCount', 
            'activeCount', 
            'totalStudents', 
            'totalCompanies'
        ));
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

    public function updateSchool(Request $request, School $school)
    {
        $request->validate([
            'npsn' => ['required', 'string', 'size:8', 'unique:schools,npsn,' . $school->id],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:100'],
        ]);

        $school->update([
            'npsn' => $request->npsn,
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        return back()->with('success', "Data profil sekolah {$school->name} berhasil diperbarui.");
    }

    public function destroySchool(School $school)
    {
        $studentCount = Student::whereHas('class', function ($q) use ($school) {
            $q->where('school_id', $school->id);
        })->count();

        if ($studentCount > 0) {
            return back()->with('error', "Sekolah {$school->name} memiliki {$studentCount} siswa terdaftar. Hapus atau arsipkan data siswa terlebih dahulu sebelum menghapus tenant ini demi keamanan data.");
        }

        DB::transaction(function () use ($school) {
            $school->settings()->delete();
            $school->subscriptions()->delete();
            $school->invoices()->delete();
            $school->users()->delete();
            $school->delete();
        });

        return back()->with('success', "Tenant sekolah {$school->name} beserta data terkait berhasil dihapus.");
    }

    public function resetAdminPassword(Request $request, School $school)
    {
        $adminUser = User::where('school_id', $school->id)
            ->where('role', 'admin-sekolah')
            ->first();

        if (!$adminUser) {
            return back()->with('error', "Tidak ditemukan akun Admin Sekolah untuk tenant {$school->name}.");
        }

        $newPassword = $request->input('new_password', 'password');
        $adminUser->update([
            'password' => Hash::make($newPassword),
            'is_active' => true,
        ]);

        return back()->with('success', "Kata sandi Admin Sekolah ({$adminUser->email}) berhasil direset menjadi: {$newPassword}");
    }

    public function exportSchools(): StreamedResponse
    {
        $schools = School::with(['subscriptions' => function ($q) {
                $q->latest('end_date');
            }, 'users' => function ($q) {
                $q->where('role', 'admin-sekolah');
            }])
            ->withCount(['users', 'classes', 'companies'])
            ->orderBy('name')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rekap-tenant-vokatrack-' . date('Ymd-His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($schools) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'NPSN',
                'Nama Sekolah',
                'Email Resmi',
                'Telepon',
                'Alamat',
                'Status Tenant',
                'Paket Lisensi',
                'Status Sewa',
                'Masa Berlaku',
                'Sisa Hari',
                'Kuota Siswa',
                'Siswa Terdaftar',
                'Jumlah Rombel',
                'Mitra DU/DI',
                'Email Admin',
            ]);

            foreach ($schools as $sch) {
                $sub = $sch->activeSubscription();
                $studentsCount = Student::whereHas('class', function ($q) use ($sch) {
                    $q->where('school_id', $sch->id);
                })->count();
                $adminEmail = $sch->users->first()?->email ?? '-';

                fputcsv($handle, [
                    $sch->npsn,
                    $sch->name,
                    $sch->email,
                    $sch->phone,
                    $sch->address,
                    $sch->is_active ? 'Aktif' : 'Nonaktif',
                    $sub?->plan_name ?? 'Belum Berlangganan',
                    $sub ? $sub->computed_status : '-',
                    $sub ? $sub->end_date->format('d/m/Y') : '-',
                    $sub ? $sub->daysRemaining() : '-',
                    $sub?->student_quota ?? 0,
                    $studentsCount,
                    $sch->classes_count,
                    $sch->companies_count,
                    $adminEmail,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function extendSubscription(Request $request, School $school)
    {
        $request->validate([
            'months' => ['required', 'integer', 'in:1,3,6,12,24'],
            'price' => ['required', 'numeric', 'min:0'],
            'new_quota' => ['nullable', 'integer', 'min:10'],
            'notes' => ['nullable', 'string', 'max:255'],
            'plan_id' => ['nullable', 'exists:subscription_plans,id'],
        ]);

        $activeSub = $school->activeSubscription();
        if (!$activeSub) {
            $defaultPlan = SubscriptionPlan::where('code', 'pro-yearly')->first() ?? SubscriptionPlan::first();
            $activeSub = $this->subscriptionService->subscribeSchool($school, $defaultPlan);
        }

        $newPlan = $request->plan_id ? SubscriptionPlan::find($request->plan_id) : null;

        $this->subscriptionService->extendSubscription(
            $activeSub,
            (int) $request->months,
            (float) $request->price,
            $request->new_quota ? (int) $request->new_quota : null,
            $request->notes,
            $newPlan
        );

        $planNotice = $newPlan ? " dan paket ditingkatkan ke {$newPlan->name}" : "";
        return back()->with('success', "Masa sewa sekolah {$school->name} berhasil diperpanjang +{$request->months} bulan{$planNotice}.");
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

        $invoices = $query->paginate(15)->withQueryString();
        $schools = School::where('is_active', true)->orderBy('name')->get();

        // Accurate KPI counts for Invoices
        $totalInvoicesCount = Invoice::count();
        $paidInvoicesCount = Invoice::where('status', 'paid')->count();
        $unpaidInvoicesCount = Invoice::where('status', 'unpaid')->count();
        $totalInvoicedAmount = (float) Invoice::sum('amount');

        return view('superadmin.invoices', compact(
            'invoices', 
            'schools', 
            'totalInvoicesCount', 
            'paidInvoicesCount', 
            'unpaidInvoicesCount', 
            'totalInvoicedAmount'
        ));
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

    public function cancelInvoice(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', "Invoice yang sudah lunas tidak dapat dibatalkan.");
        }

        $invoice->update(['status' => 'cancelled']);
        return back()->with('success', "Invoice {$invoice->invoice_number} berhasil dibatalkan.");
    }

    public function destroyInvoice(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', "Invoice yang sudah berstatus LUNAS tidak dapat dihapus demi audit finansial.");
        }

        $number = $invoice->invoice_number;
        $invoice->delete();
        return back()->with('success', "Invoice tagihan {$number} berhasil dihapus.");
    }

    public function downloadInvoicePdf(Invoice $invoice)
    {
        $invoice->load(['school', 'subscription']);
        $platformName = PlatformSetting::get('platform_name', 'VokaTrack');
        $platformTagline = PlatformSetting::get('platform_tagline', 'Enterprise Cloud');
        $systemLogoUrl = PlatformSetting::getLogoUrl();

        $pdf = Pdf::loadView('superadmin.invoice_pdf', compact(
            'invoice',
            'platformName',
            'platformTagline',
            'systemLogoUrl'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }

    public function plans()
    {
        if (SubscriptionPlan::count() === 0) {
            (new \Database\Seeders\SubscriptionPlanSeeder())->run();
        }

        $plans = SubscriptionPlan::withCount('subscriptions')->get();

        // Accurate KPI metrics for Plans
        $totalPlans = $plans->count();
        $activePlans = $plans->where('is_active', true)->count();
        $totalActiveSchools = SchoolSubscription::whereIn('status', ['active', 'trial', 'expiring_soon'])->distinct('school_id')->count('school_id');
        $totalCapacityLicensed = SchoolSubscription::sum('student_quota');

        return view('superadmin.plans', compact(
            'plans',
            'totalPlans',
            'activePlans',
            'totalActiveSchools',
            'totalCapacityLicensed'
        ));
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
        if ($plan->code === 'trial-14d') {
            return back()->with('error', "Paket {$plan->name} adalah paket demo bawaan sistem dan tidak dapat dihapus.");
        }

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
        $maintenanceActive = (bool) PlatformSetting::get('maintenance_active', false);
        $maintenanceNotice = PlatformSetting::get('maintenance_notice', '');

        // System Health & Diagnostics
        $systemHealth = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_driver' => config('database.default'),
            'db_name' => config('database.connections.' . config('database.default') . '.database'),
            'app_env' => app()->environment(),
            'app_debug' => config('app.debug') ? 'Aktif (Warning)' : 'Nonaktif (Production Safe)',
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'storage_writable' => is_writable(storage_path()),
        ];

        return view('superadmin.settings', compact(
            'currentLogoUrl',
            'hasCustomLogo',
            'platformName',
            'platformTagline',
            'appDescription',
            'maintenanceActive',
            'maintenanceNotice',
            'systemHealth'
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
            'maintenance_active' => ['nullable', 'boolean'],
            'maintenance_notice' => ['nullable', 'string', 'max:500'],
        ]);

        PlatformSetting::set('platform_name', $request->platform_name);
        PlatformSetting::set('platform_tagline', $request->platform_tagline ?: 'Enterprise Cloud');
        PlatformSetting::set('app_description', $request->app_description ?: '');
        PlatformSetting::set('maintenance_active', $request->boolean('maintenance_active') ? '1' : '0');
        PlatformSetting::set('maintenance_notice', $request->maintenance_notice ?: '');

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

        return back()->with('success', 'Pengaturan branding dan konfigurasi sistem berhasil diperbarui.');
    }
}
