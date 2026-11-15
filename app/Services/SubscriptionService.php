<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\Student;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService
{
    /**
     * Get SaaS operational & revenue metrics for the Superadmin dashboard.
     */
    public function getSaaSMetrics(): array
    {
        $today = Carbon::today();
        $thirtyDaysFromNow = Carbon::today()->addDays(30);

        $totalSchools = School::count();
        $activeSchools = School::where('is_active', true)->count();

        // Calculate metrics per tenant based on their active subscription
        $schools = School::with(['subscriptions' => function ($q) {
            $q->latest('end_date');
        }])->get();

        $activeTenants = 0;
        $trialTenants = 0;
        $expiringSoon = 0;
        $totalQuotaLicensed = 0;

        foreach ($schools as $sch) {
            $sub = $sch->activeSubscription();
            if ($sub) {
                $computed = $sub->computed_status;
                if ($computed === 'trial') {
                    $trialTenants++;
                } elseif ($computed === 'active' || $computed === 'expiring_soon' || $computed === 'grace_period') {
                    $activeTenants++;
                }

                if (!$sub->end_date->isPast() && $sub->end_date->lte($thirtyDaysFromNow)) {
                    $expiringSoon++;
                }

                $totalQuotaLicensed += $sub->student_quota;
            }
        }

        // Total lifetime revenue collected (hanya invoice yang benar-benar lunas)
        $totalRevenue = (float) Invoice::where('status', 'paid')->sum('amount');

        // Active students registered across platform
        $totalStudents = Student::count();

        // Calculate Monthly Recurring Revenue estimate from active paying subscriptions
        $mrrEstimate = 0;
        $arrEstimate = 0;
        foreach ($schools as $sch) {
            $sub = $sch->activeSubscription();
            if ($sub && in_array($sub->computed_status, ['active', 'expiring_soon', 'grace_period'], true)) {
                $price = (float) $sub->price_paid;
                if ($price > 0) {
                    $arrEstimate += $price;
                    $mrrEstimate += ($price / 12);
                }
            }
        }

        // Invoices summary
        $pendingInvoicesCount = Invoice::where('status', 'unpaid')
            ->orWhere('status', 'pending_verification')
            ->count();

        return [
            'total_schools' => $totalSchools,
            'active_schools' => $activeSchools,
            'active_tenants' => $activeTenants,
            'trial_tenants' => $trialTenants,
            'expiring_soon' => $expiringSoon,
            'total_students' => $totalStudents,
            'total_quota_licensed' => max($totalQuotaLicensed, $totalStudents),
            'total_revenue' => $totalRevenue,
            'mrr_estimate' => round($mrrEstimate),
            'arr_estimate' => round($arrEstimate),
            'pending_invoices_count' => $pendingInvoicesCount,
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
     * Assign or renew a subscription for a school.
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
        
        // If it's a 14-day trial package or status is trial, end in 14 days
        if ($plan->code === 'trial-14d' || ($status === 'trial' && Str::contains($plan->name, '14'))) {
            $end = (clone $start)->addDays(14);
        } else {
            $months = match ($plan->billing_period) {
                'monthly' => 1,
                'semester' => 6,
                'yearly' => 12,
                default => 12,
            };
            $end = (clone $start)->addMonths($months);
        }

        return DB::transaction(function () use ($school, $plan, $start, $end, $customQuota, $customPrice, $status, $notes, $customStorageMb, $createInvoice, $paymentStatus, $paymentMethod) {
            $subscription = SchoolSubscription::create([
                'school_id' => $school->id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'start_date' => $start,
                'end_date' => $end,
                'price_paid' => $customPrice !== null ? $customPrice : $plan->price,
                'student_quota' => $customQuota !== null ? $customQuota : $plan->max_students,
                'storage_quota_mb' => $customStorageMb !== null ? $customStorageMb : $plan->max_storage_mb,
                'status' => $status,
                'notes' => $notes,
            ]);

            if ($createInvoice) {
                $amount = (float) $subscription->price_paid;
                $this->createInvoice(
                    $subscription,
                    "Biaya Sewa {$plan->name} ({$school->name})",
                    $amount,
                    // Paket gratis/trial tidak memiliki tagihan yang perlu ditunggu.
                    $amount > 0 ? $paymentStatus : 'paid',
                    $amount > 0 ? $paymentMethod : 'Trial Evaluasi',
                    14,
                    'Aktivasi lisensi sewa via Super Administrator'
                );
            }

            return $subscription;
        });
    }

    /**
     * Extend an existing subscription.
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
        $newEndDate = $subscription->end_date->isPast()
            ? Carbon::today()->addMonths($additionalMonths)
            : $subscription->end_date->copy()->addMonths($additionalMonths);

        $updateData = [
            'end_date' => $newEndDate,
            'status' => 'active',
            'price_paid' => $subscription->price_paid + $price,
            'student_quota' => $newQuota ?? ($newPlan ? $newPlan->max_students : $subscription->student_quota),
            'notes' => $notes ? trim($subscription->notes . "\n" . $notes) : $subscription->notes,
        ];

        // Upgrade paket dan kuota storage kustom diproses independen:
        // kuota kustom hanya menimpa batas storage bawaan paket baru.
        if ($newPlan) {
            $updateData['plan_id'] = $newPlan->id;
            $updateData['plan_name'] = $newPlan->name;
            $updateData['storage_quota_mb'] = $newPlan->max_storage_mb;
        }
        if ($customStorageMb !== null) {
            $updateData['storage_quota_mb'] = $customStorageMb;
        }

        $subscription->update($updateData);

        if ($price > 0) {
            $this->createInvoice(
                $subscription,
                "Perpanjangan Sewa {$subscription->plan_name} (+{$additionalMonths} Bulan)",
                $price,
                $paymentStatus,
                $paymentMethod,
                7,
                'Perpanjangan sewa hingga ' . $newEndDate->format('d M Y')
            );
        }

        return $subscription;
    }

    /**
     * Terbitkan invoice langganan. Status 'paid' hanya bila pembayaran memang sudah diterima.
     */
    protected function createInvoice(
        SchoolSubscription $subscription,
        string $title,
        float $amount,
        string $paymentStatus,
        ?string $paymentMethod,
        int $dueInDays,
        string $notes
    ): Invoice {
        $isPaid = $paymentStatus === 'paid';

        return Invoice::create([
            'invoice_number' => 'INV-' . date('Ym') . '-' . strtoupper(Str::random(5)),
            'school_id' => $subscription->school_id,
            'subscription_id' => $subscription->id,
            'title' => $title,
            'amount' => $amount,
            'due_date' => Carbon::today()->addDays($dueInDays),
            'status' => $isPaid ? 'paid' : 'unpaid',
            'payment_method' => $isPaid ? ($paymentMethod ?: 'Pembayaran manual (dicatat Super Admin)') : $paymentMethod,
            'paid_at' => $isPaid ? Carbon::now() : null,
            'notes' => $notes,
        ]);
    }
}
