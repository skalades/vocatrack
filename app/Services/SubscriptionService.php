<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\Student;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public const TRIAL_DAYS = 7;

    /**
     * Metrik operasional & pendapatan untuk dashboard Super Admin.
     * Seluruh perhitungan memakai satu kali eager load (tanpa query per sekolah).
     */
    public function getSaaSMetrics(): array
    {
        $lifecycle = app(TenantLifecycleService::class);
        $noticeLimit = Carbon::today()->addDays(TenantLifecycleService::RENEWAL_NOTICE_DAYS);

        $schools = School::with('subscriptions')->get();
        $stageCounts = array_fill_keys(array_keys(TenantLifecycleService::STAGES), 0);

        $expiringSoon = 0;
        $totalQuotaLicensed = 0;
        $mrrEstimate = 0.0;

        foreach ($schools as $school) {
            $sub = $school->activeSubscription();
            $stage = $lifecycle->stage($school, $sub);
            $stageCounts[$stage]++;

            if (!$sub) {
                continue;
            }

            if (!$sub->end_date->isPast() && $sub->end_date->lte($noticeLimit)) {
                $expiringSoon++;
            }
            $totalQuotaLicensed += $sub->student_quota;

            // MRR = harga periode berjalan dibagi panjang periodenya (bulan).
            if (in_array($stage, ['active', 'expiring', 'grace'], true) && (float) $sub->price_paid > 0) {
                $months = max(1, (int) round($sub->start_date->diffInMonths($sub->end_date)));
                $mrrEstimate += (float) $sub->price_paid / $months;
            }
        }

        $totalStudents = Student::count();
        $openStatuses = ['unpaid', 'pending_verification'];

        return [
            'total_schools' => $schools->count(),
            'active_schools' => $schools->where('is_active', true)->count(),
            'active_tenants' => $stageCounts['active'] + $stageCounts['expiring'] + $stageCounts['grace'],
            'trial_tenants' => $stageCounts['trial'],
            'expiring_soon' => $expiringSoon,
            'stage_counts' => $stageCounts,
            'total_students' => $totalStudents,
            'total_quota_licensed' => max($totalQuotaLicensed, $totalStudents),
            'total_revenue' => (float) Invoice::where('status', 'paid')->sum('amount'),
            'outstanding_amount' => (float) Invoice::whereIn('status', $openStatuses)->sum('amount'),
            'mrr_estimate' => round($mrrEstimate),
            'arr_estimate' => round($mrrEstimate * 12),
            'pending_invoices_count' => Invoice::whereIn('status', $openStatuses)->count(),
        ];
    }

    /**
     * Check if a school can add student(s) based on quota.
     */
    public function canAddStudent(School $school, int $count = 1): array
    {
        $subscription = $school->activeSubscription();
        if (!$subscription) {
            return [
                'allowed' => false,
                'message' => 'Sekolah belum memiliki paket langganan aktif. Hubungi Super Administrator.',
            ];
        }

        if ($subscription->isExpired() && !$subscription->isGracePeriod()) {
            return [
                'allowed' => false,
                'message' => 'Masa berlaku sewa sistem telah habis (' . $subscription->end_date->format('d M Y') . '). Hubungi Super Administrator untuk perpanjangan lisensi.',
            ];
        }

        $currentStudentCount = Student::whereHas('class', function ($q) use ($school) {
            $q->where('school_id', $school->id);
        })->count();

        $quota = $subscription->student_quota;
        if ($currentStudentCount + $count > $quota) {
            return [
                'allowed' => false,
                'message' => "Kuota siswa terlisensi ({$quota} siswa) telah terpenuhi. Saat ini terdaftar {$currentStudentCount} siswa. Harap tingkatkan paket langganan sekolah untuk menambah {$count} siswa lagi.",
            ];
        }

        return [
            'allowed' => true,
            'current' => $currentStudentCount,
            'quota' => $quota,
            'remaining' => $quota - $currentStudentCount,
        ];
    }

    /**
     * Buat periode langganan secara langsung (onboarding / seeder / data uji).
     * Bila $createInvoice, invoice aktivasi ikut diterbitkan dan ditandai sudah diterapkan.
     * Untuk paket berbayar yang belum dibayar gunakan onboard().
     */
    public function subscribeSchool(
        School $school,
        SubscriptionPlan $plan,
        ?Carbon $startDate = null,
        ?int $customQuota = null,
        ?float $customPrice = null,
        string $status = 'active',
        ?string $notes = null,
        ?int $customStorageMb = null,
        bool $createInvoice = true,
        string $paymentStatus = 'paid',
        ?string $paymentMethod = null
    ): SchoolSubscription {
        $start = $startDate ?? Carbon::today();
        $isTrial = $status === 'trial' || $plan->code === 'trial-14d';
        $end = $isTrial ? $start->copy()->addDays(self::TRIAL_DAYS) : $start->copy()->addMonths($plan->periodMonths());

        return DB::transaction(function () use ($school, $plan, $start, $end, $customQuota, $customPrice, $status, $notes, $customStorageMb, $createInvoice, $paymentStatus, $paymentMethod, $isTrial) {
            $subscription = SchoolSubscription::create([
                'school_id' => $school->id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'start_date' => $start,
                'end_date' => $end,
                'price_paid' => $customPrice !== null ? $customPrice : $plan->price,
                'student_quota' => $customQuota !== null ? $customQuota : $plan->max_students,
                'storage_quota_mb' => $customStorageMb !== null ? $customStorageMb : $plan->max_storage_mb,
                'status' => $isTrial ? 'trial' : $status,
                'notes' => $notes,
            ]);

            if ($createInvoice) {
                $amount = (float) $subscription->price_paid;
                $isPaid = $amount <= 0 || $paymentStatus === 'paid';

                Invoice::create([
                    'invoice_number' => app(TenantLifecycleService::class)->nextInvoiceNumber(),
                    'school_id' => $school->id,
                    'subscription_id' => $subscription->id,
                    'type' => 'activation',
                    'plan_id' => $plan->id,
                    'period_months' => $isTrial ? null : $plan->periodMonths(),
                    'title' => "Biaya Sewa {$plan->name} ({$school->name})",
                    'amount' => $amount,
                    'due_date' => Carbon::today()->addDays(14),
                    'status' => $isPaid ? 'paid' : 'unpaid',
                    'payment_method' => $amount <= 0
                        ? 'Trial Evaluasi'
                        : ($isPaid ? ($paymentMethod ?: 'Pembayaran manual (dicatat Super Admin)') : $paymentMethod),
                    'paid_at' => $isPaid ? Carbon::now() : null,
                    // Periode sudah dibuat langsung di atas; invoice tidak boleh menerapkannya lagi.
                    'applied_at' => Carbon::now(),
                    'notes' => 'Aktivasi lisensi sewa via Super Administrator',
                ]);
            }

            return $subscription;
        });
    }

    /**
     * Onboarding sekolah baru. Paket gratis/trial atau paket berbayar yang sudah lunas langsung
     * aktif; paket berbayar yang belum lunas diberi masa trial singkat (TRIAL_DAYS) plus tagihan aktivasi
     * yang otomatis mengaktifkan paket begitu dilunasi.
     */
    public function onboard(
        School $school,
        SubscriptionPlan $plan,
        bool $paid,
        ?string $paymentMethod = null,
        ?int $customQuota = null,
        ?int $customStorageMb = null,
        ?string $notes = null
    ): SchoolSubscription {
        if ($plan->isTrialPlan() || $paid) {
            return $this->subscribeSchool(
                $school, $plan, Carbon::today(), $customQuota, null,
                $plan->isTrialPlan() ? 'trial' : 'active',
                $notes, $customStorageMb, true, 'paid', $paymentMethod
            );
        }

        return DB::transaction(function () use ($school, $plan, $paymentMethod, $customQuota, $customStorageMb, $notes) {
            $trial = $this->subscribeSchool(
                $school, $plan, Carbon::today(), $customQuota, 0, 'trial',
                trim(($notes ? $notes . ' — ' : '') . 'Trial sambil menunggu pembayaran aktivasi'),
                $customStorageMb, false
            );

            $months = $plan->periodMonths();
            $invoice = app(TenantLifecycleService::class)->issueInvoice(
                $school, 'activation', "Aktivasi {$plan->name} ({$months} Bulan)", (float) $plan->price,
                $plan, $months, $customQuota, $customStorageMb, null, self::TRIAL_DAYS
            );
            if ($paymentMethod) {
                $invoice->update(['payment_method' => $paymentMethod]);
            }

            return $trial;
        });
    }

    /**
     * Perpanjang / upgrade langganan (kompatibilitas pemanggil lama).
     * Bila dibayar, periode baru langsung aktif; bila belum, hanya tagihan yang terbit.
     */
    public function extendSubscription(
        SchoolSubscription $subscription,
        int $additionalMonths,
        float $price = 0,
        ?int $newQuota = null,
        ?string $notes = null,
        ?SubscriptionPlan $newPlan = null,
        ?int $customStorageMb = null,
        string $paymentStatus = 'paid',
        ?string $paymentMethod = null
    ): SchoolSubscription {
        $plan = $newPlan ?? $subscription->plan ?? SubscriptionPlan::firstOrFail();

        $result = app(TenantLifecycleService::class)->renew(
            $subscription->school,
            $plan,
            $additionalMonths,
            $price,
            $paymentStatus === 'paid',
            $paymentMethod,
            $newQuota,
            $customStorageMb,
            $notes
        );

        return $result['period'] ?? $subscription;
    }
}
