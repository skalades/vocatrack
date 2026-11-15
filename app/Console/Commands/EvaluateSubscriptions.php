<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\TenantLifecycleService;
use Illuminate\Console\Command;

class EvaluateSubscriptions extends Command
{
    protected $signature = 'saas:evaluate-subscriptions';

    protected $description = 'Evaluasi harian siklus hidup tenant: sinkron status tenggang/kedaluwarsa, terbitkan tagihan perpanjangan H-30, kirim pengingat H-7 & H-1';

    public function handle(TenantLifecycleService $lifecycle): int
    {
        $this->info('[SAAS] Memulai evaluasi harian siklus hidup tenant...');

        $counts = array_fill_keys(array_keys(TenantLifecycleService::STAGES), 0);

        School::with(['subscriptions.plan'])->chunkById(100, function ($schools) use ($lifecycle, &$counts) {
            foreach ($schools as $school) {
                try {
                    $stage = $lifecycle->evaluate($school);
                    $counts[$stage]++;
                } catch (\Throwable $e) {
                    report($e);
                    $this->error("[GAGAL] {$school->name}: {$e->getMessage()}");
                }
            }
        });

        $rows = collect($counts)
            ->map(fn ($count, $stage) => [TenantLifecycleService::stageMeta($stage)['label'], $count])
            ->values()
            ->all();

        $this->table(['Tahap', 'Jumlah Sekolah'], $rows);
        $this->info('[SAAS] Evaluasi selesai.');

        return self::SUCCESS;
    }
}
