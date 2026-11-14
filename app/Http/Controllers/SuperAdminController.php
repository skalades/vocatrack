<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\PlatformAnnouncement;
use App\Models\PlatformSetting;
use App\Models\School;
use App\Models\SchoolRequest;
use App\Models\SchoolSetting;
use App\Models\SchoolSubscription;
use App\Models\Student;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\BackupService;
use App\Services\ImpersonationService;
use App\Services\StorageQuotaService;
use App\Services\SubscriptionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuperAdminController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected StorageQuotaService $storageQuotaService,
        protected BackupService $backupService
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
        $platformStorage = $this->storageQuotaService->getPlatformStorageStats();

        return view('superadmin.schools', compact(
            'schools', 
            'plans', 
            'totalCount', 
            'activeCount', 
            'totalStudents', 
            'totalCompanies',
            'platformStorage'
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
            'custom_storage_mb' => ['nullable', 'integer', 'min:100'],
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
            $customStorageMb = $request->custom_storage_mb ? (int) $request->custom_storage_mb : null;
            $status = $plan->price > 0 ? 'active' : 'trial';

            $this->subscriptionService->subscribeSchool(
                $school,
                $plan,
                Carbon::today(),
                $customQuota,
                null,
                $status,
                "Aktivasi awal tenant sekolah ({$plan->name})",
                $customStorageMb
            );

            return $school;
        });

        AuditLogService::log(
            'school_create',
            'schools',
            "Tenant sekolah {$school->name} (NPSN: {$school->npsn}) berhasil didaftarkan.",
            $school,
            null,
            null,
            ['npsn' => $school->npsn, 'name' => $school->name, 'plan_id' => $request->plan_id]
        );

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

        $before = $school->only(['npsn', 'name', 'address', 'phone', 'email']);

        $school->update([
            'npsn' => $request->npsn,
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        AuditLogService::log(
            'school_update',
            'schools',
            "Data profil sekolah {$school->name} berhasil diperbarui.",
            $school,
            null,
            $before,
            $school->only(['npsn', 'name', 'address', 'phone', 'email'])
        );

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

        $schoolName = $school->name;
        $schoolNpsn = $school->npsn;

        DB::transaction(function () use ($school) {
            $school->settings()->delete();
            $school->subscriptions()->delete();
            $school->invoices()->delete();
            $school->users()->delete();
            $school->delete();
        });

        AuditLogService::log(
            'school_delete',
            'schools',
            "Tenant sekolah {$schoolName} (NPSN: {$schoolNpsn}) dihapus oleh Super Admin.",
            null,
            null,
            ['school_name' => $schoolName, 'npsn' => $schoolNpsn]
        );

        return back()->with('success', "Tenant sekolah {$schoolName} beserta data terkait berhasil dihapus.");
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

        AuditLogService::log(
            'password_reset',
            'security',
            "Kata sandi Admin Sekolah ({$adminUser->email}) untuk tenant {$school->name} direset oleh Super Admin.",
            $school
        );

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
            'custom_storage_mb' => ['nullable', 'integer', 'min:100'],
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
            $newPlan,
            $request->custom_storage_mb ? (int) $request->custom_storage_mb : null
        );

        $planNotice = $newPlan ? " dan paket ditingkatkan ke {$newPlan->name}" : "";

        AuditLogService::log(
            'subscription_extend',
            'billing',
            "Masa sewa tenant {$school->name} diperpanjang +{$request->months} bulan (Rp " . number_format($request->price, 0, ',', '.') . "){$planNotice}.",
            $school
        );

        return back()->with('success', "Masa sewa sekolah {$school->name} berhasil diperpanjang +{$request->months} bulan{$planNotice}.");
    }

    public function toggleSchool(School $school)
    {
        $school->update(['is_active' => !$school->is_active]);

        $status = $school->is_active ? 'diaktifkan' : 'dinonaktifkan (seluruh sesi user diblokir)';

        AuditLogService::log(
            'school_toggle',
            'schools',
            "Status tenant sekolah {$school->name} diubah menjadi: {$status}.",
            $school
        );

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

        AuditLogService::log(
            'invoice_create',
            'billing',
            "Faktur tagihan {$invoiceNumber} untuk {$school->name} senilai Rp " . number_format($request->amount, 0, ',', '.') . " berhasil diterbitkan.",
            $school
        );

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

        AuditLogService::log(
            'invoice_verify',
            'billing',
            "Faktur tagihan {$invoice->invoice_number} senilai Rp " . number_format($invoice->amount, 0, ',', '.') . " diverifikasi LUNAS.",
            $invoice->school
        );

        return back()->with('success', "Invoice {$invoice->invoice_number} berhasil ditandai LUNAS.");
    }

    public function cancelInvoice(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', "Invoice yang sudah lunas tidak dapat dibatalkan.");
        }

        $invoice->update(['status' => 'cancelled']);

        AuditLogService::log(
            'invoice_cancel',
            'billing',
            "Faktur tagihan {$invoice->invoice_number} dibatalkan oleh Super Admin.",
            $invoice->school
        );

        return back()->with('success', "Invoice {$invoice->invoice_number} berhasil dibatalkan.");
    }

    public function destroyInvoice(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', "Invoice yang sudah berstatus LUNAS tidak dapat dihapus demi audit finansial.");
        }

        $number = $invoice->invoice_number;
        $school = $invoice->school;
        $invoice->delete();

        AuditLogService::log(
            'invoice_delete',
            'billing',
            "Faktur tagihan {$number} dihapus dari sistem.",
            $school
        );

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

        AuditLogService::log(
            'settings_update',
            'settings',
            "Pengaturan branding dan konfigurasi sistem diperbarui oleh Super Admin."
        );

        return back()->with('success', 'Pengaturan branding dan konfigurasi sistem berhasil diperbarui.');
    }

    // ==========================================
    // 1. AUDIT TRAIL & LOG AKTIVITAS PLATFORM
    // ==========================================

    public function auditLogs(Request $request)
    {
        $query = ActivityLog::with(['school', 'user'])->latest();

        if ($request->filled('module') && $request->module !== 'all') {
            $query->where('module', $request->module);
        }

        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        if ($request->filled('school_id') && $request->school_id !== 'all') {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('school', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")->orWhere('npsn', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $logs = $query->paginate(20)->withQueryString();
        $schools = School::orderBy('name')->get();

        // Metrics for Audit Logs
        $totalLogsToday = ActivityLog::whereDate('created_at', Carbon::today())->count();
        $impersonationEventsCount = ActivityLog::whereIn('action', ['impersonation_start', 'impersonation_end'])->count();
        $securityEventsCount = ActivityLog::where('module', 'security')->count();
        $totalLogsAll = ActivityLog::count();

        return view('superadmin.audit_logs', compact(
            'logs',
            'schools',
            'totalLogsToday',
            'impersonationEventsCount',
            'securityEventsCount',
            'totalLogsAll'
        ));
    }

    // ==========================================
    // 2. DISK & STORAGE QUOTA MANAGEMENT
    // ==========================================

    public function recalculateSchoolStorage(School $school)
    {
        $bytes = $this->storageQuotaService->calculateSchoolStorage($school);
        $mb = round($bytes / (1024 * 1024), 2);

        AuditLogService::log(
            'storage_recalculated',
            'system',
            "Penggunaan ruang disk tenant {$school->name} dihitung ulang: {$mb} MB.",
            $school
        );

        return back()->with('success', "Penggunaan ruang disk tenant {$school->name} berhasil diperbarui: {$mb} MB.");
    }

    public function updateSchoolStorageQuota(Request $request, School $school)
    {
        $request->validate([
            'storage_quota_mb' => ['required', 'integer', 'min:100', 'max:1048576'],
        ]);

        $sub = $school->activeSubscription();
        if (!$sub) {
            return back()->with('error', "Tenant {$school->name} belum memiliki langganan aktif.");
        }

        $oldQuota = $sub->storage_quota_mb;
        $sub->update(['storage_quota_mb' => $request->storage_quota_mb]);

        AuditLogService::log(
            'storage_quota_updated',
            'schools',
            "Batas kuota ruang disk (storage) sekolah {$school->name} diubah dari {$oldQuota} MB menjadi {$request->storage_quota_mb} MB.",
            $school,
            null,
            ['old_quota_mb' => $oldQuota],
            ['new_quota_mb' => $request->storage_quota_mb]
        );

        return back()->with('success', "Batas kuota storage sekolah {$school->name} berhasil diperbarui menjadi {$request->storage_quota_mb} MB (~" . round($request->storage_quota_mb / 1024, 2) . " GB).");
    }

    // ==========================================
    // 3. DEVOPS, QUEUES & ERROR LOG VIEWER
    // ==========================================

    public function systemHealth(Request $request)
    {
        // 1. Failed Jobs
        $failedJobs = DB::table('failed_jobs')->latest('failed_at')->take(20)->get();
        $failedJobsCount = DB::table('failed_jobs')->count();

        // 2. Queue Backlog Jobs
        $pendingJobsCount = DB::table('jobs')->count();

        // 3. System Storage Breakdown
        $storageStats = $this->storageQuotaService->getPlatformStorageStats();

        // 4. Live Laravel Logs (recent 60 entries)
        $logPath = storage_path('logs/laravel.log');
        $logLines = [];
        if (File::exists($logPath)) {
            $content = File::get($logPath);
            $pattern = '/^\[(?<date>.*?)\]\s(?<env>\w+?)\.(?<level>\w+?):(?<message>.*?)(?=\n\[|\z)/ms';
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                $matches = array_reverse($matches);
                foreach (array_slice($matches, 0, 40) as $m) {
                    $logLines[] = [
                        'date' => $m['date'] ?? '-',
                        'level' => strtoupper($m['level'] ?? 'INFO'),
                        'message' => trim($m['message'] ?? ''),
                    ];
                }
            }
        }

        // Filter log level if requested
        if ($request->filled('log_level') && $request->log_level !== 'all') {
            $targetLevel = strtoupper($request->log_level);
            $logLines = array_filter($logLines, fn($l) => $l['level'] === $targetLevel);
        }

        // Diagnostics
        $systemHealth = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_driver' => config('database.default'),
            'db_name' => config('database.connections.' . config('database.default') . '.database'),
            'app_env' => app()->environment(),
            'app_debug' => config('app.debug') ? 'Aktif (Warning)' : 'Nonaktif (Production Safe)',
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_connection' => config('queue.default'),
            'storage_writable' => is_writable(storage_path()),
        ];

        return view('superadmin.system_health', compact(
            'systemHealth',
            'failedJobs',
            'failedJobsCount',
            'pendingJobsCount',
            'storageStats',
            'logLines'
        ));
    }

    public function retryFailedJob(string $id)
    {
        try {
            Artisan::call('queue:retry', ['id' => [$id]]);
            AuditLogService::log('queue_retry', 'devops', "Job antrean #{$id} di-retry oleh Super Admin.");
            return back()->with('success', "Pekerjaan antrean #{$id} berhasil dimasukkan ulang ke antrean.");
        } catch (\Throwable $e) {
            return back()->with('error', "Gagal me-retry job: " . $e->getMessage());
        }
    }

    public function deleteFailedJob(string $id)
    {
        DB::table('failed_jobs')->where('id', $id)->delete();
        AuditLogService::log('queue_delete', 'devops', "Failed job #{$id} dihapus dari daftar.");
        return back()->with('success', "Failed job #{$id} berhasil dihapus.");
    }

    public function flushFailedJobs()
    {
        DB::table('failed_jobs')->truncate();
        AuditLogService::log('queue_flush', 'devops', "Seluruh riwayat failed jobs dibersihkan oleh Super Admin.");
        return back()->with('success', "Seluruh antrean failed jobs berhasil dikosongkan.");
    }

    public function clearSystemCache(Request $request)
    {
        $type = $request->input('type', 'all');

        try {
            if ($type === 'cache' || $type === 'all') {
                Artisan::call('cache:clear');
            }
            if ($type === 'config' || $type === 'all') {
                Artisan::call('config:clear');
            }
            if ($type === 'view' || $type === 'all') {
                Artisan::call('view:clear');
            }
            if ($type === 'route' || $type === 'all') {
                Artisan::call('route:clear');
            }

            AuditLogService::log('cache_cleared', 'system', "Pembersihan cache platform tipe ({$type}) dijalankan.");
            return back()->with('success', "Cache sistem ({$type}) berhasil dibersihkan.");
        } catch (\Throwable $e) {
            return back()->with('error', "Gagal membersihkan cache: " . $e->getMessage());
        }
    }

    // ==========================================
    // 4. CADANGAN DATA & PEMULIHAN TENANT
    // ==========================================

    public function backups()
    {
        $backups = $this->backupService->listBackups();
        $schools = School::where('is_active', true)->orderBy('name')->get();
        $totalBackupsCount = count($backups);

        return view('superadmin.backups', compact('backups', 'schools', 'totalBackupsCount'));
    }

    public function createBackup()
    {
        try {
            $backup = $this->backupService->createDatabaseBackup();

            AuditLogService::log(
                'backup_create',
                'system',
                "Cadangan data snapshot {$backup['filename']} ({$backup['size_formatted']}) berhasil dibuat."
            );

            return back()->with('success', "Cadangan data platform {$backup['filename']} berhasil dibuat ({$backup['size_formatted']}).");
        } catch (\Throwable $e) {
            return back()->with('error', "Gagal membuat cadangan basis data: " . $e->getMessage());
        }
    }

    public function downloadBackup(string $filename)
    {
        $safeFilename = basename($filename);
        $filePath = storage_path("app/backups/{$safeFilename}");

        if (!File::exists($filePath)) {
            return back()->with('error', "Berkas cadangan {$safeFilename} tidak ditemukan.");
        }

        AuditLogService::log('backup_download', 'system', "Berkas snapshot cadangan {$safeFilename} diunduh.");
        return response()->download($filePath);
    }

    public function deleteBackup(string $filename)
    {
        $safeFilename = basename($filename);
        $deleted = $this->backupService->deleteBackup($safeFilename);

        if ($deleted) {
            AuditLogService::log('backup_delete', 'system', "Berkas snapshot cadangan {$safeFilename} dihapus.");
            return back()->with('success', "Berkas cadangan {$safeFilename} berhasil dihapus.");
        }

        return back()->with('error', "Gagal menghapus berkas cadangan {$safeFilename}.");
    }

    public function exportSchoolArchive(School $school)
    {
        try {
            $zipPath = $this->backupService->exportSchoolDataArchive($school);

            AuditLogService::log(
                'tenant_offboard_export',
                'schools',
                "Arsip data lengkap offboarding untuk {$school->name} berhasil diunduh.",
                $school
            );

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return back()->with('error', "Gagal mengemas arsip data sekolah: " . $e->getMessage());
        }
    }

    // ==========================================
    // 5. SIARAN PENGUMUMAN GLOBAL (BROADCASTS)
    // ==========================================

    public function broadcasts()
    {
        $broadcasts = PlatformAnnouncement::with('creator')->latest()->paginate(15);
        $totalBroadcasts = PlatformAnnouncement::count();
        $activeBroadcasts = PlatformAnnouncement::where('is_active', true)->count();

        return view('superadmin.broadcasts', compact('broadcasts', 'totalBroadcasts', 'activeBroadcasts'));
    }

    public function storeBroadcast(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string'],
            'type' => ['required', 'in:info,warning,emergency,update'],
            'target_role' => ['required', 'in:all,admin-sekolah,guru-pembimbing,siswa,pembimbing-dudi'],
            'is_dismissible' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $announcement = PlatformAnnouncement::create([
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'target_role' => $request->target_role,
            'is_active' => true,
            'is_dismissible' => $request->boolean('is_dismissible', true),
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at,
            'created_by' => Auth::id(),
        ]);

        AuditLogService::log(
            'broadcast_create',
            'announcement',
            "Siaran pengumuman global '{$announcement->title}' tipe [{$announcement->type}] diterbitkan."
        );

        return back()->with('success', "Siaran pengumuman global '{$announcement->title}' berhasil dipublikasikan.");
    }

    public function updateBroadcast(Request $request, PlatformAnnouncement $broadcast)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string'],
            'type' => ['required', 'in:info,warning,emergency,update'],
            'target_role' => ['required', 'in:all,admin-sekolah,guru-pembimbing,siswa,pembimbing-dudi'],
            'is_dismissible' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $broadcast->update([
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'target_role' => $request->target_role,
            'is_dismissible' => $request->boolean('is_dismissible', true),
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at,
        ]);

        AuditLogService::log(
            'broadcast_update',
            'announcement',
            "Siaran pengumuman global '{$broadcast->title}' diperbarui."
        );

        return back()->with('success', "Siaran pengumuman '{$broadcast->title}' berhasil diperbarui.");
    }

    public function toggleBroadcast(PlatformAnnouncement $broadcast)
    {
        $broadcast->update(['is_active' => !$broadcast->is_active]);
        $status = $broadcast->is_active ? 'diaktifkan' : 'dinonaktifkan';

        AuditLogService::log(
            'broadcast_toggle',
            'announcement',
            "Status siaran pengumuman '{$broadcast->title}' diubah menjadi {$status}."
        );

        return back()->with('success', "Siaran pengumuman '{$broadcast->title}' berhasil {$status}.");
    }

    public function destroyBroadcast(PlatformAnnouncement $broadcast)
    {
        $title = $broadcast->title;
        $broadcast->delete();

        AuditLogService::log('broadcast_delete', 'announcement', "Siaran pengumuman '{$title}' dihapus.");
        return back()->with('success', "Siaran pengumuman '{$title}' berhasil dihapus.");
    }

    // ==========================================
    // 6. PIPELINE PERMOHONAN DEMO & ONBOARDING
    // ==========================================

    public function schoolRequests(Request $request)
    {
        $query = SchoolRequest::with(['school', 'reviewer'])->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('school_name', 'like', "%{$search}%")
                  ->orWhere('npsn', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(15)->withQueryString();
        $plans = SubscriptionPlan::where('is_active', true)->get();

        $totalRequests = SchoolRequest::count();
        $pendingRequests = SchoolRequest::where('status', 'pending')->count();
        $approvedRequests = SchoolRequest::where('status', 'approved')->count();

        return view('superadmin.school_requests', compact(
            'requests',
            'plans',
            'totalRequests',
            'pendingRequests',
            'approvedRequests'
        ));
    }

    public function approveSchoolRequest(Request $request, SchoolRequest $schoolRequest)
    {
        $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'custom_quota' => ['nullable', 'integer', 'min:10'],
        ]);

        if (School::where('npsn', $schoolRequest->npsn)->exists()) {
            return back()->with('error', "Sekolah dengan NPSN {$schoolRequest->npsn} sudah terdaftar di sistem.");
        }

        if (User::where('email', $schoolRequest->email)->exists()) {
            return back()->with('error', "Email kontak {$schoolRequest->email} sudah digunakan oleh akun lain.");
        }

        $school = DB::transaction(function () use ($schoolRequest, $request) {
            $school = School::create([
                'npsn' => $schoolRequest->npsn,
                'name' => $schoolRequest->school_name,
                'address' => $schoolRequest->address ?: '-',
                'phone' => $schoolRequest->phone,
                'email' => $schoolRequest->email,
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

            // Create School Admin User
            User::create([
                'school_id' => $school->id,
                'name' => $schoolRequest->contact_name,
                'email' => $schoolRequest->email,
                'password' => Hash::make('password'),
                'role' => 'admin-sekolah',
                'is_active' => true,
            ]);

            // Subscribe School
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
                "Aktivasi via Permohonan Demo Onboarding ({$plan->name})"
            );

            // Mark Request as approved
            $schoolRequest->update([
                'status' => 'approved',
                'school_id' => $school->id,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => Carbon::now(),
            ]);

            return $school;
        });

        AuditLogService::log(
            'school_request_approved',
            'schools',
            "Permohonan demo untuk sekolah {$school->name} (NPSN: {$school->npsn}) disetujui dan tenant dibuat.",
            $school
        );

        return back()->with('success', "Permohonan disetujui! Tenant {$school->name} berhasil dibuat. Akun Admin ({$schoolRequest->email}) siap digunakan dengan password default: password");
    }

    public function rejectSchoolRequest(Request $request, SchoolRequest $schoolRequest)
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:255'],
        ]);

        $schoolRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => Carbon::now(),
        ]);

        AuditLogService::log(
            'school_request_rejected',
            'schools',
            "Permohonan demo untuk sekolah {$schoolRequest->school_name} (NPSN: {$schoolRequest->npsn}) ditolak. Alasan: {$request->rejection_reason}"
        );

        return back()->with('success', "Permohonan demo untuk {$schoolRequest->school_name} telah ditolak.");
    }

    public function storePublicSchoolRequest(Request $request)
    {
        $request->validate([
            'npsn' => ['required', 'string', 'size:8'],
            'school_name' => ['required', 'string', 'max:150'],
            'contact_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'phone' => ['required', 'string', 'max:25'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        if (School::where('npsn', $request->npsn)->exists()) {
            return back()->with('error', "Sekolah dengan NPSN {$request->npsn} telah terdaftar aktif di platform VokaTrack.");
        }

        $pending = SchoolRequest::where('npsn', $request->npsn)->where('status', 'pending')->first();
        if ($pending) {
            return back()->with('info', "Permohonan untuk NPSN {$request->npsn} telah diajukan dan sedang dalam proses verifikasi tim kami.");
        }

        SchoolRequest::create([
            'npsn' => $request->npsn,
            'school_name' => $request->school_name,
            'contact_name' => $request->contact_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        return back()->with('success', "Terima kasih! Permohonan pendaftaran akun demo VokaTrack untuk {$request->school_name} telah diterima. Tim kami akan segera meninjau dan menghubungi Anda.");
    }
}
