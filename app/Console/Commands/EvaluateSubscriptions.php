<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\SchoolSubscription;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EvaluateSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'saas:evaluate-subscriptions';

    /**
     * The console command description.
     */
    protected $description = 'Evaluasi harian masa berlaku langganan tenant sekolah, transisi status tenggang (grace period) & suspend lisensi';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('[SAAS] Memulai evaluasi harian lisensi sekolah...');

        $today = Carbon::today();
        $schools = School::with(['subscriptions' => function ($q) {
            $q->latest('end_date');
        }])->get();

        $activeCount = 0;
        $graceCount = 0;
        $expiredCount = 0;
        $expiringSoonCount = 0;

        foreach ($schools as $school) {
            $subscription = $school->activeSubscription();
            if (!$subscription) {
                continue;
            }

            $daysRemaining = $subscription->daysRemaining();

            // 1. Subscription has expired
            if ($subscription->end_date->isPast()) {
                if ($daysRemaining >= -7) {
                    // Grace Period (7 days tolerance)
                    if ($subscription->status !== 'grace_period') {
                        $subscription->update(['status' => 'grace_period']);
                        AuditLogService::log(
                            'subscription_grace_period',
                            'billing',
                            "Masa sewa sekolah {$school->name} telah habis. Tenant dialihkan ke Masa Tenggang (Grace Period 7 Hari).",
                            $school
                        );
                        $this->warn("[GRACE PERIOD] {$school->name}: sisa tenggang " . (7 + $daysRemaining) . " hari.");
                    }
                    $graceCount++;
                } else {
                    // Past grace period -> Expired
                    if ($subscription->status !== 'expired' && $subscription->status !== 'suspended') {
                        $subscription->update(['status' => 'expired']);
                        AuditLogService::log(
                            'subscription_expired',
                            'billing',
                            "Masa tenggang sewa sekolah {$school->name} telah berakhir. Akses pengguna diblokir.",
                            $school
                        );
                        $this->error("[EXPIRED] {$school->name}: lisensi dinonaktifkan.");
                    }
                    $expiredCount++;
                }
            } else {
                // Subscription is active
                $activeCount++;
                if ($daysRemaining <= 14) {
                    $expiringSoonCount++;
                    $this->line("[EXPIRING SOON] {$school->name}: berakhir dalam {$daysRemaining} hari ({$subscription->end_date->format('d M Y')}).");
                }
            }
        }

        $this->info("[SAAS] Evaluasi selesai. Aktif: {$activeCount}, Menjelang Habis: {$expiringSoonCount}, Masa Tenggang: {$graceCount}, Kedaluwarsa: {$expiredCount}.");

        return Command::SUCCESS;
    }
}
