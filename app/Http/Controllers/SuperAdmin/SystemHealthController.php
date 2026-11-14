<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\StorageQuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SystemHealthController extends Controller
{
    public function __construct(
        protected StorageQuotaService $storageQuotaService
    ) {}

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
}
