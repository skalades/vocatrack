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

        // Subscriptions
        $subscriptions = SchoolSubscription::all();

        $activeTenants = 0;
        $trialTenants = 0;
        $expiringSoon = 0;
        $totalQuotaLicensed = 0;
        $totalRevenue = 0;

        foreach ($subscriptions as $sub) {
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
            $totalRevenue += (float) $sub->price_paid;
        }

        // Active students registered across platform
        $totalStudents = Student::count();

        // Calculate Monthly Recurring Revenue estimate
        // If yearly, amortize to / 12
        $mrrEstimate = 0;
        $arrEstimate = 0;
        foreach ($subscriptions as $sub) {
            if ($sub->status === 'active' || $sub->status === 'trial') {
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
        ?string $notes = null
    ): SchoolSubscription {
        $start = $startDate ?? Carbon::today();
        
        $months = match ($plan->billing_period) {
            'monthly' => 1,
            'semester' => 6,
            'yearly' => 12,
            default => 12,
        };

        $end = (clone $start)->addMonths($months);

        return DB::transaction(function () use ($school, $plan, $start, $end, $customQuota, $customPrice, $status, $notes) {
            $subscription = SchoolSubscription::create([
                'school_id' => $school->id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'start_date' => $start,
                'end_date' => $end,
                'price_paid' => $customPrice !== null ? $customPrice : $plan->price,
                'student_quota' => $customQuota !== null ? $customQuota : $plan->max_students,
                'storage_quota_mb' => $plan->max_storage_mb,
                'status' => $status,
                'notes' => $notes,
            ]);

            // Create initial invoice
            $invoiceNumber = 'INV-' . date('Ym') . '-' . strtoupper(Str::random(5));
            Invoice::create([
                'invoice_number' => $invoiceNumber,
                'school_id' => $school->id,
                'subscription_id' => $subscription->id,
                'title' => "Biaya Sewa {$plan->name} ({$school->name})",
                'amount' => $subscription->price_paid,
                'due_date' => Carbon::today()->addDays(14),
                'status' => $subscription->price_paid > 0 ? 'paid' : 'paid',
                'payment_method' => $subscription->price_paid > 0 ? 'Transfer Bank / APBD BOS' : 'Trial Evaluasi',
                'paid_at' => Carbon::now(),
                'notes' => 'Aktivasi lisensi sewa via Super Administrator',
            ]);

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
        ?string $notes = null
    ): SchoolSubscription {
        $newEndDate = $subscription->end_date->isPast()
            ? Carbon::today()->addMonths($additionalMonths)
            : $subscription->end_date->copy()->addMonths($additionalMonths);

        $subscription->update([
            'end_date' => $newEndDate,
            'status' => 'active',
            'price_paid' => $subscription->price_paid + $price,
            'student_quota' => $newQuota ?? $subscription->student_quota,
            'notes' => $notes ? ($subscription->notes . "\n" . $notes) : $subscription->notes,
        ]);

        if ($price > 0) {
            $invoiceNumber = 'INV-' . date('Ym') . '-' . strtoupper(Str::random(5));
            Invoice::create([
                'invoice_number' => $invoiceNumber,
                'school_id' => $subscription->school_id,
                'subscription_id' => $subscription->id,
                'title' => "Perpanjangan Sewa {$subscription->plan_name} (+{$additionalMonths} Bulan)",
                'amount' => $price,
                'due_date' => Carbon::today()->addDays(7),
                'status' => 'paid',
                'payment_method' => 'Transfer Bank BNI / Mandiri',
                'paid_at' => Carbon::now(),
                'notes' => "Perpanjangan sewa hingga " . $newEndDate->format('d M Y'),
            ]);
        }

        return $subscription;
    }
}
