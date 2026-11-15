<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\AdjustStorageQuotaRequest;
use App\Http\Requests\SuperAdmin\ExtendSubscriptionRequest;
use App\Http\Requests\SuperAdmin\StoreSchoolRequest;
use App\Http\Requests\SuperAdmin\UpdateSchoolRequest;
use App\Models\School;
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
use App\Services\TenantProvisioningService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected StorageQuotaService $storageQuotaService,
        protected BackupService $backupService
    ) {}

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

        // 1. Search Query
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

    public function storeSchool(StoreSchoolRequest $request, TenantProvisioningService $provisioning)
    {
        [$school, $temporaryPassword] = DB::transaction(function () use ($request, $provisioning) {
            [$school, , $temporaryPassword] = $provisioning->provision(
                $request->only(['npsn', 'name', 'address', 'phone', 'email']),
                $request->admin_name,
                $request->admin_email
            );

            // Activate Subscription Plan
            $plan = SubscriptionPlan::findOrFail($request->plan_id);
            $customQuota = $request->custom_quota ? (int) $request->custom_quota : null;
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
                $customStorageMb,
                true,
                $request->input('payment_status', 'paid'),
                $request->payment_method
            );

            return [$school, $temporaryPassword];
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

        return back()
            ->with('success', "Sekolah {$school->name} dan paket sewa berhasil didaftarkan.")
            ->with('temporary_credential', [
                'email' => $request->admin_email,
                'password' => $temporaryPassword,
                'context' => "Admin Sekolah {$school->name}",
            ]);
    }

    public function updateSchool(UpdateSchoolRequest $request, School $school)
    {
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

    public function destroySchool(Request $request, School $school)
    {
        if ($school->is_active) {
            return back()->with('error', "Nonaktifkan tenant {$school->name} terlebih dahulu sebelum menghapusnya secara permanen.");
        }

        if (trim((string) $request->input('confirm_npsn')) !== (string) $school->npsn) {
            return back()->with('error', "Konfirmasi gagal: ketik NPSN {$school->npsn} dengan benar untuk menghapus tenant ini.");
        }

        $studentCount = Student::whereHas('class', function ($q) use ($school) {
            $q->where('school_id', $school->id);
        })->count();

        if ($studentCount > 0) {
            return back()->with('error', "Sekolah {$school->name} memiliki {$studentCount} siswa terdaftar. Hapus atau arsipkan data siswa terlebih dahulu sebelum menghapus tenant ini demi keamanan data.");
        }

        $schoolName = $school->name;
        $schoolNpsn = $school->npsn;
        $schoolId = $school->id;
        $brandingFiles = array_filter([$school->logo_path, $school->stamp_path]);

        DB::transaction(function () use ($school) {
            // Hapus eksplisit dari tabel anak ke induk agar tidak bergantung pada urutan
            // cascade DB (classes.major_id & student_pkl.company_id bersifat RESTRICT).
            $schema = DB::getSchemaBuilder();
            $majorIds = DB::table('majors')->where('school_id', $school->id)->pluck('id');
            $companyIds = DB::table('companies')->where('school_id', $school->id)->pluck('id');

            if ($majorIds->isNotEmpty()) {
                foreach (['major_teacher', 'company_major'] as $pivot) {
                    if ($schema->hasTable($pivot)) {
                        DB::table($pivot)->whereIn('major_id', $majorIds)->delete();
                    }
                }
            }
            if ($companyIds->isNotEmpty()) {
                if ($schema->hasTable('company_major')) {
                    DB::table('company_major')->whereIn('company_id', $companyIds)->delete();
                }
                if ($schema->hasTable('supervisors')) {
                    DB::table('supervisors')->whereIn('company_id', $companyIds)->delete();
                }
            }

            foreach (array_reverse(BackupService::BACKUP_TABLES) as $table) {
                // Log audit & riwayat permohonan dipertahankan (FK nullOnDelete).
                if (in_array($table, ['schools', 'activity_logs', 'school_requests'], true)) {
                    continue;
                }
                if ($schema->hasTable($table) && $schema->hasColumn($table, 'school_id')) {
                    DB::table($table)->where('school_id', $school->id)->delete();
                }
            }

            $school->delete();
        });

        foreach ($brandingFiles as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
        Storage::disk('local')->deleteDirectory("schools/{$schoolId}");
        Storage::disk('public')->deleteDirectory("schools/{$schoolId}");
        Cache::forget("school_storage_bytes_{$schoolId}");

        AuditLogService::log(
            'school_delete',
            'schools',
            "Tenant sekolah {$schoolName} (NPSN: {$schoolNpsn}) dihapus permanen oleh Super Admin.",
            null,
            null,
            ['school_name' => $schoolName, 'npsn' => $schoolNpsn]
        );

        return back()->with('success', "Tenant sekolah {$schoolName} beserta data terkait berhasil dihapus.");
    }

    public function resetAdminPassword(Request $request, School $school)
    {
        $validated = $request->validate([
            'new_password' => ['nullable', 'string', 'min:8', 'max:100'],
        ], [
            'new_password.min' => 'Kata sandi baru minimal 8 karakter. Kosongkan untuk dibuatkan otomatis.',
        ]);

        $adminUser = User::where('school_id', $school->id)
            ->where('role', 'admin-sekolah')
            ->orderBy('id')
            ->first();

        if (!$adminUser) {
            return back()->with('error', "Tidak ditemukan akun Admin Sekolah untuk tenant {$school->name}.");
        }

        $newPassword = $validated['new_password'] ?? TenantProvisioningService::generateTemporaryPassword();

        $adminUser->update([
            'password' => $newPassword,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        // Putus seluruh sesi aktif akun tersebut agar kata sandi lama tidak bisa dipakai lagi.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $adminUser->id)->delete();
        }

        AuditLogService::log(
            'password_reset',
            'security',
            "Kata sandi Admin Sekolah ({$adminUser->email}) untuk tenant {$school->name} direset oleh Super Admin.",
            $school
        );

        return back()
            ->with('success', "Kata sandi Admin Sekolah ({$adminUser->email}) berhasil direset. Pengguna wajib menggantinya saat login berikutnya.")
            ->with('temporary_credential', [
                'email' => $adminUser->email,
                'password' => $newPassword,
                'context' => "Admin Sekolah {$school->name}",
            ]);
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

    public function extendSubscription(ExtendSubscriptionRequest $request, School $school)
    {
        $newPlan = $request->plan_id ? SubscriptionPlan::find($request->plan_id) : null;

        $activeSub = $school->activeSubscription();
        if (!$activeSub) {
            // Kerangka langganan tanpa invoice yang berakhir hari ini, sehingga perpanjangan
            // di bawah menjadi satu-satunya tagihan (mencegah tagihan ganda).
            $basePlan = $newPlan
                ?? SubscriptionPlan::where('code', 'pro-yearly')->first()
                ?? SubscriptionPlan::firstOrFail();
            $activeSub = $this->subscriptionService->subscribeSchool(
                $school,
                $basePlan,
                Carbon::today(),
                null,
                0,
                'active',
                'Langganan dibuat saat perpanjangan oleh Super Admin',
                null,
                false
            );
            $activeSub->update(['end_date' => Carbon::today()]);
        }

        $this->subscriptionService->extendSubscription(
            $activeSub,
            (int) $request->months,
            (float) $request->price,
            $request->new_quota ? (int) $request->new_quota : null,
            $request->notes,
            $newPlan,
            $request->custom_storage_mb ? (int) $request->custom_storage_mb : null,
            $request->input('payment_status', 'paid'),
            $request->payment_method
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
            $adminUser = User::where('school_id', $school->id)->where('is_active', true)->first();
        }

        if (!$adminUser) {
            return back()->with('error', "Tidak ditemukan akun administrator aktif untuk sekolah {$school->name}.");
        }

        $service->startImpersonation(Auth::user(), $adminUser);

        return redirect()->route('adminsekolah.dashboard')->with('success', "Mode Impersonasi Super Admin Aktif: Anda saat ini mengelola tenant {$school->name} sebagai {$adminUser->name}.");
    }

    public function recalculateSchoolStorage(School $school)
    {
        try {
            $bytes = $this->storageQuotaService->calculateSchoolStorage($school);
            $mb = round($bytes / (1024 * 1024), 2);

            AuditLogService::log(
                'storage_recalculated',
                'system',
                "Penggunaan ruang disk tenant {$school->name} dihitung ulang: {$mb} MB.",
                $school
            );

            return redirect()->route('superadmin.schools')->with('success', "Penggunaan ruang disk tenant {$school->name} berhasil diperbarui: {$mb} MB.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("[STORAGE RECALCULATE ERROR] Failed for school {$school->id}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            return redirect()->route('superadmin.schools')->with('error', "Gagal menghitung ulang storage: " . $e->getMessage());
        }
    }

    public function updateSchoolStorageQuota(AdjustStorageQuotaRequest $request, School $school)
    {
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
}
