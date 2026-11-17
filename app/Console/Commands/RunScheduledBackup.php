<?php

namespace App\Console\Commands;

use App\Services\AuditLogService;
use App\Services\BackupRetentionPolicy;
use App\Services\BackupService;
use App\Services\SystemAlertService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class RunScheduledBackup extends Command
{
    protected $signature = 'vokatrack:backup
        {--prefix= : Prefix nama berkas (default: prefix cadangan otomatis)}
        {--no-upload : Jangan unggah ke penyimpanan off-site}
        {--no-prune : Jangan jalankan retensi (hapus cadangan lama)}';

    protected $description = 'Buat cadangan database terenkripsi, unggah ke penyimpanan off-site, lalu terapkan retensi';

    public const STATUS_CACHE_KEY = 'vokatrack:backup:last_run';

    public function handle(BackupService $backups, SystemAlertService $alerts): int
    {
        $autoPrefix = (string) config('backup.auto_prefix');
        $prefix = $this->option('prefix') ?: $autoPrefix;
        $disk = config('backup.offsite_disk');
        $offsitePath = (string) config('backup.offsite_path');
        $backup = null;
        $uploaded = false;

        try {
            $this->info('[BACKUP] Membuat snapshot database terenkripsi...');
            $backup = $backups->createDatabaseBackup($prefix);
            $this->info("[BACKUP] Snapshot lokal: {$backup['filename']} ({$backup['size_formatted']})");

            if ($this->option('no-upload')) {
                $this->warn('[BACKUP] Unggah off-site dilewati (--no-upload).');
            } elseif (blank($disk)) {
                $this->warn('[BACKUP] BACKUP_OFFSITE_DISK belum diatur — cadangan HANYA tersimpan di server ini.');
            } else {
                $this->upload($disk, "{$offsitePath}/{$backup['filename']}", $backup['path'], (int) $backup['size']);
                $uploaded = true;
                $this->info("[BACKUP] Terunggah ke disk '{$disk}': {$offsitePath}/{$backup['filename']}");
            }

            if (!$this->option('no-prune')) {
                foreach ((array) config('backup.managed_prefixes', [$autoPrefix]) as $managed) {
                    $this->pruneLocal($backups->backupDirectory(), $managed);
                    if (filled($disk)) {
                        $this->pruneOffsite($disk, $offsitePath, $managed);
                    }
                }
            }
        } catch (\Throwable $e) {
            report($e);

            $what = $backup ? "Snapshot lokal {$backup['filename']} dibuat, tetapi unggah/retensi gagal" : 'Snapshot database gagal dibuat';
            $message = "{$what}: {$e->getMessage()}";

            $this->recordStatus('failed', $backup['filename'] ?? null, $uploaded, $message);
            AuditLogService::log('backup_scheduled_failed', 'system', $message);
            $alerts->notifySuperAdmins(
                'backup_failed',
                'Cadangan otomatis gagal',
                $message,
                route('superadmin.backups'),
            );

            $this->error("[BACKUP] {$message}");

            return self::FAILURE;
        }

        $this->recordStatus('success', $backup['filename'], $uploaded, null);
        AuditLogService::log(
            'backup_scheduled',
            'system',
            "Cadangan otomatis {$backup['filename']} ({$backup['size_formatted']}) dibuat" . ($uploaded ? " dan diunggah ke '{$disk}'." : ' (lokal saja).')
        );

        $this->info('[BACKUP] Selesai.');

        return self::SUCCESS;
    }

    protected function upload(string $disk, string $remotePath, string $localPath, int $expectedSize): void
    {
        $storage = Storage::disk($disk);
        $stream = fopen($localPath, 'rb');

        try {
            if ($storage->writeStream($remotePath, $stream) === false) {
                throw new \RuntimeException("Gagal mengunggah {$remotePath} ke disk '{$disk}'.");
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $remoteSize = $storage->size($remotePath);
        if ($remoteSize !== $expectedSize) {
            throw new \RuntimeException("Ukuran berkas off-site ({$remoteSize} B) tidak sama dengan lokal ({$expectedSize} B).");
        }
    }

    /**
     * Hapus cadangan otomatis lokal yang lebih tua dari batas hari (salinan off-site tetap ada).
     */
    protected function pruneLocal(string $directory, string $autoPrefix): void
    {
        $keepDays = max((int) config('backup.local_keep_days', 7), 1);
        $cutoff = CarbonImmutable::now()->subDays($keepDays);
        $deleted = 0;

        foreach (File::files($directory) as $file) {
            $name = $file->getFilename();
            if (!str_starts_with($name, $autoPrefix . '_')) {
                continue;
            }
            $ts = BackupRetentionPolicy::timestampFromFilename($name);
            if ($ts !== null && $ts->lt($cutoff) && File::delete($file->getPathname())) {
                $deleted++;
            }
        }

        $this->line("[BACKUP] Retensi lokal {$autoPrefix}: {$deleted} berkas lama dihapus (simpan {$keepDays} hari).");
    }

    protected function pruneOffsite(string $disk, string $path, string $autoPrefix): void
    {
        $storage = Storage::disk($disk);

        $files = array_values(array_filter(
            $storage->files($path),
            fn ($file) => str_starts_with(basename($file), $autoPrefix . '_')
        ));

        $toDelete = BackupRetentionPolicy::fromConfig()->filesToDelete($files);
        if ($toDelete !== []) {
            $storage->delete($toDelete);
        }

        $this->line("[BACKUP] Retensi off-site {$autoPrefix}: " . count($toDelete) . ' berkas lama dihapus, ' . (count($files) - count($toDelete)) . ' dipertahankan.');
    }

    protected function recordStatus(string $status, ?string $filename, bool $uploaded, ?string $error): void
    {
        try {
            Cache::forever(self::STATUS_CACHE_KEY, [
                'status' => $status,
                'filename' => $filename,
                'offsite' => $uploaded,
                'error' => $error,
                'at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
