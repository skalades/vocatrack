<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\AuditLogService;
use App\Services\BackupService;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService
    ) {}

    public function backups()
    {
        $backups = $this->backupService->listBackups();
        $schools = School::where('is_active', true)->orderBy('name')->get();
        $totalBackupsCount = count($backups);

        return view('superadmin.backups', compact('backups', 'schools', 'totalBackupsCount'));
    }

    public function createBackup()
    {
        try {
            $backup = $this->backupService->createDatabaseBackup();

            AuditLogService::log(
                'backup_create',
                'system',
                "Cadangan data snapshot {$backup['filename']} ({$backup['size_formatted']}) berhasil dibuat."
            );

            return back()->with('success', "Cadangan data platform {$backup['filename']} berhasil dibuat ({$backup['size_formatted']}).");
        } catch (\Throwable $e) {
            return back()->with('error', "Gagal membuat cadangan basis data: " . $e->getMessage());
        }
    }

    public function downloadBackup(string $filename)
    {
        $safeFilename = basename($filename);
        $filePath = storage_path("app/backups/{$safeFilename}");

        if (!File::exists($filePath)) {
            return back()->with('error', "Berkas cadangan {$safeFilename} tidak ditemukan.");
        }

        AuditLogService::log('backup_download', 'system', "Berkas snapshot cadangan {$safeFilename} diunduh.");
        return response()->download($filePath);
    }

    public function deleteBackup(string $filename)
    {
        $safeFilename = basename($filename);
        $deleted = $this->backupService->deleteBackup($safeFilename);

        if ($deleted) {
            AuditLogService::log('backup_delete', 'system', "Berkas snapshot cadangan {$safeFilename} dihapus.");
            return back()->with('success', "Berkas cadangan {$safeFilename} berhasil dihapus.");
        }

        return back()->with('error', "Gagal menghapus berkas cadangan {$safeFilename}.");
    }

    public function restoreBackup(string $filename)
    {
        try {
            $safeFilename = basename($filename);
            $res = $this->backupService->restoreDatabaseBackup($safeFilename);

            AuditLogService::log(
                'backup_restore',
                'system',
                "Pemulihan data platform dari snapshot {$safeFilename} ({$res['tables_count']} tabel) berhasil diselesaikan."
            );

            return back()->with('success', "Basis data platform berhasil dipulihkan dari snapshot {$safeFilename} ({$res['tables_count']} tabel).");
        } catch (\Throwable $e) {
            return back()->with('error', "Gagal memulihkan cadangan data: " . $e->getMessage());
        }
    }
}
