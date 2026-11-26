<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\AdjustStorageQuotaRequest;
use App\Http\Requests\SuperAdmin\ExtendSubscriptionRequest;
use App\Http\Requests\SuperAdmin\StoreSchoolRequest;
use App\Http\Requests\SuperAdmin\UpdateSchoolRequest;
use App\Models\ActivityLog;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\BackupService;
use App\Services\ImpersonationService;
use App\Services\StorageQuotaService;
use App\Services\SubscriptionService;
use App\Services\TenantLifecycleService;
use App\Services\TenantProvisioningService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

    /**
     * Daftar sekolah dikelompokkan per tahap siklus hidup.
     */
    public function schools(Request $request, TenantLifecycleService $lifecycle)
    {
        $query = School::with(['subscriptions'])
            ->withCount(['invoices as open_invoices_count' => fn ($q) => $q->whereIn('status', ['unpaid', 'pending_verification'])]);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('npsn', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $all = $query->orderBy('name')->get();
        $stages = $lifecycle->stagesFor($all);

        $stageCounts = array_fill_keys(array_keys(TenantLifecycleService::STAGES), 0);
        foreach ($stages as $stage) {
            $stageCounts[$stage]++;
        }

        // Kompatibilitas filter lama (?status=...) dipetakan ke tahap baru.
        $legacy = ['inactive' => 'suspended', 'expiring_soon' => 'expiring'];
        $stageFilter = $request->query('stage', $legacy[$request->query('status')] ?? $request->query('status'));
        if ($stageFilter === 'attention') {
            $filtered = $all->filter(fn ($s) => in_array($stages[$s->id], ['expiring', 'grace', 'expired'], true) || $s->open_invoices_count > 0);
        } elseif ($stageFilter && isset(TenantLifecycleService::STAGES[$stageFilter])) {
            $filtered = $all->filter(fn ($s) => $stages[$s->id] === $stageFilter);
        } else {
            $stageFilter = 'all';
            $filtered = $all;
        }

        $perPage = 15;
        $page = max(1, (int) $request->query('page', 1));
        $schools = new LengthAwarePaginator(
            $filtered->slice(($page - 1) * $perPage, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Jumlah siswa per sekolah dalam satu query agregat.
        $studentCounts = DB::table('students')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->whereIn('classes.school_id', $schools->pluck('id'))
            ->selectRaw('classes.school_id, COUNT(*) as total')
            ->groupBy('classes.school_id')
            ->pluck('total', 'school_id');

        $plans = SubscriptionPlan::where('is_active', true)->orderBy('price')->get();
        $attentionCount = $all->filter(fn ($s) => in_array($stages[$s->id], ['expiring', 'grace', 'expired'], true) || $s->open_invoices_count > 0)->count();

        return view('superadmin.schools', compact(
            'schools', 'stages', 'stageCounts', 'stageFilter', 'studentCounts', 'plans', 'attentionCount'
        ) + ['totalCount' => $all->count()]);
    }

    /**
     * Halaman Detail Sekolah: pusat kendali satu tenant sepanjang siklus hidupnya.
     */
    public function show(Request $request, School $school, TenantLifecycleService $lifecycle)
    {
        $tabs = ['ringkasan', 'langganan', 'tagihan', 'pengguna', 'aktivitas', 'pengaturan'];
        $tab = in_array($request->query('tab'), $tabs, true) ? $request->query('tab') : 'ringkasan';

        $school->load(['subscriptions' => fn ($q) => $q->with('plan')->orderByDesc('end_date')->orderByDesc('id')]);
        $subscription = $school->activeSubscription();
        $stage = $lifecycle->stage($school, $subscription);

        $openInvoice = $school->invoices()
            ->whereIn('status', ['unpaid', 'pending_verification'])
            ->orderByRaw("CASE WHEN type IN ('activation','renewal','upgrade') THEN 0 ELSE 1 END")
            ->orderBy('due_date')
            ->first();

        $studentsCount = Student::whereHas('class', fn ($q) => $q->where('school_id', $school->id))->count();
        $roleCounts = User::where('school_id', $school->id)
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $data = compact('school', 'subscription', 'stage', 'openInvoice', 'studentsCount', 'roleCounts', 'tab');
        $data['stageMeta'] = TenantLifecycleService::stageMeta($stage);
        $data['classesCount'] = $school->classes()->count();
        $data['companiesCount'] = $school->companies()->count();
        $data['storageUsedMb'] = round(((int) $school->storage_used_bytes) / (1024 * 1024), 1);
        $data['plans'] = SubscriptionPlan::where('is_active', true)->orderBy('price')->get();
        $data['schoolSettings'] = SchoolSetting::allForSchool($school->id);

        if ($tab === 'tagihan') {
            $data['invoices'] = $school->invoices()->with('plan')->latest()->paginate(15)->withQueryString();
        }
        if ($tab === 'pengguna') {
            $data['managers'] = User::where('school_id', $school->id)
                ->whereIn('role', ['admin-sekolah', 'kepala-sekolah'])
                ->orderBy('role')->orderBy('name')->get();

            // Wali Kelas & Kajur adalah tugas tambahan pada akun guru-pembimbing (relasi kelas & jurusan).
            $data['waliKelasCount'] = $school->classes()
                ->whereNotNull('homeroom_teacher_id')
                ->distinct()
                ->count('homeroom_teacher_id');
            $data['kajurCount'] = $school->majors()
                ->whereNotNull('head_teacher_id')
                ->distinct()
                ->count('head_teacher_id');
        }
        if ($tab === 'aktivitas') {
            $data['logs'] = ActivityLog::with('user')->where('school_id', $school->id)->latest()->paginate(20)->withQueryString();
        }

        return view('superadmin.school_show', $data);
    }

    public function storeSchool(StoreSchoolRequest $request, TenantProvisioningService $provisioning)
    {
        [$school, $temporaryPassword] = DB::transaction(function () use ($request, $provisioning) {
            [$school, , $temporaryPassword] = $provisioning->provision(
                $request->only(['npsn', 'name', 'address', 'phone', 'email']),
                $request->admin_name,
                $request->admin_email
            );

            $this->subscriptionService->onboard(
                $school,
                SubscriptionPlan::findOrFail($request->plan_id),
                $request->input('payment_status', 'paid') === 'paid',
                $request->payment_method,
                $request->custom_quota ? (int) $request->custom_quota : null,
                $request->custom_storage_mb ? (int) $request->custom_storage_mb : null,
                'Aktivasi awal tenant sekolah'
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

    public function updateSchoolFeatures(Request $request, School $school)
    {
        $features = [
            'feature_checkin_selfie',
            'feature_checkin_geofence',
            'feature_checkout_selfie',
            'feature_checkout_geofence',
            'feature_whatsapp',
            'feature_journal_photo',
            'feature_parent_portal',
        ];

        $before = [];
        $after = [];
        foreach ($features as $key) {
            $before[$key] = $school->isFeatureEnabled($key, true) ? '1' : '0';
            $newValue = $request->boolean($key) ? '1' : '0';
            $after[$key] = $newValue;

            SchoolSetting::updateOrCreate(
                ['school_id' => $school->id, 'key' => $key],
                ['value' => $newValue]
            );
        }

        AuditLogService::log(
            'school_features_updated',
            'schools',
            "Pengaturan fitur dan kebijakan tenant {$school->name} berhasil diperbarui.",
            $school,
            null,
            $before,
            $after
        );

        return redirect()->route('superadmin.schools.show', ['school' => $school->id, 'tab' => 'pengaturan'])
            ->with('success', "Pengaturan fitur dan kebijakan tenant {$school->name} berhasil disimpan.");
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

    public function extendSubscription(ExtendSubscriptionRequest $request, School $school, TenantLifecycleService $lifecycle)
    {
        $current = $school->activeSubscription();
        $plan = ($request->plan_id ? SubscriptionPlan::find($request->plan_id) : null)
            ?? $current?->plan
            ?? SubscriptionPlan::where('code', 'pro-yearly')->first()
            ?? SubscriptionPlan::firstOrFail();

        $paid = $request->input('payment_status', 'paid') === 'paid';

        $result = $lifecycle->renew(
            $school,
            $plan,
            (int) $request->months,
            (float) $request->price,
            $paid,
            $request->payment_method,
            $request->new_quota ? (int) $request->new_quota : null,
            $request->custom_storage_mb ? (int) $request->custom_storage_mb : null,
            $request->notes
        );

        $invoice = $result['invoice'];
        $period = $result['period'];

        AuditLogService::log(
            'subscription_extend',
            'billing',
            "{$invoice->type_label} {$school->name}: {$plan->name} +{$request->months} bulan ({$invoice->formatted_amount}) — "
                . ($period ? "aktif s.d. {$period->end_date->format('d M Y')}." : 'menunggu pembayaran.'),
            $school
        );

        $message = $period
            ? "{$invoice->type_label} {$school->name} tercatat lunas. {$plan->name} aktif hingga {$period->end_date->format('d M Y')}."
            : "Tagihan {$invoice->invoice_number} ({$invoice->formatted_amount}) diterbitkan. Periode baru aktif otomatis setelah dilunasi.";

        return back()->with('success', $message);
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

            return back()->with('success', "Penggunaan ruang disk tenant {$school->name} berhasil diperbarui: {$mb} MB.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("[STORAGE RECALCULATE ERROR] Failed for school {$school->id}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->with('error', "Gagal menghitung ulang storage: " . $e->getMessage());
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
