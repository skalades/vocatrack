<?php

namespace App\Console\Commands;

use App\Services\SystemAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckFailedJobs extends Command
{
    protected $signature = 'vokatrack:check-failed-jobs {--minutes=60 : Rentang waktu yang diperiksa}';

    protected $description = 'Kirim peringatan ke super admin bila ada job antrean yang gagal dalam rentang waktu terakhir';

    public function handle(SystemAlertService $alerts): int
    {
        if (!Schema::hasTable('failed_jobs')) {
            return self::SUCCESS;
        }

        $minutes = max((int) $this->option('minutes'), 1);
        $recent = DB::table('failed_jobs')->where('failed_at', '>=', now()->subMinutes($minutes))->count();

        if ($recent === 0) {
            $this->info('[QUEUE] Tidak ada job gagal.');

            return self::SUCCESS;
        }

        $total = DB::table('failed_jobs')->count();
        $alerts->notifySuperAdmins(
            'failed_jobs',
            'Job antrean gagal',
            "{$recent} job gagal dalam {$minutes} menit terakhir (total {$total} di failed_jobs). Periksa log/Sentry lalu jalankan `php artisan queue:retry all` bila sudah diperbaiki.",
            route('superadmin.system-health'),
            'amber',
        );

        $this->warn("[QUEUE] {$recent} job gagal dalam {$minutes} menit terakhir — super admin diberi tahu.");

        return self::SUCCESS;
    }
}
