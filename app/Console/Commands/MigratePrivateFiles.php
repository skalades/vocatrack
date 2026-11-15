<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\IncidentReport;
use App\Models\JournalPhoto;
use App\Services\SecureFileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Pindahkan berkas pribadi siswa yang tersimpan di disk publik (dapat diakses via
 * /storage/...) ke disk privat. Path di database tidak berubah, sehingga aman
 * dijalankan berulang kali (idempoten).
 */
class MigratePrivateFiles extends Command
{
    protected $signature = 'vokatrack:migrate-private-files {--dry-run : Hanya tampilkan jumlah berkas tanpa memindahkan}';

    protected $description = 'Pindahkan selfie presensi, foto jurnal, dan lampiran siswa dari disk publik ke disk privat';

    public function handle(): int
    {
        $public = Storage::disk(SecureFileService::LEGACY_DISK);
        $private = Storage::disk(SecureFileService::DISK);
        $dryRun = (bool) $this->option('dry-run');

        $sources = [
            [Attendance::class, 'checkin_photo_path'],
            [JournalPhoto::class, 'photo_path'],
            [AttendanceRequest::class, 'attachment_path'],
            [IncidentReport::class, 'attachment_path'],
        ];

        $moved = 0;
        $missing = 0;

        foreach ($sources as [$modelClass, $column]) {
            $modelClass::query()->withoutGlobalScopes()
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->select('id', $column)
                ->chunkById(500, function ($rows) use ($column, $public, $private, $dryRun, &$moved, &$missing) {
                    foreach ($rows as $row) {
                        $path = $row->{$column};

                        if (!$public->exists($path)) {
                            if (!$private->exists($path)) {
                                $missing++;
                            }
                            continue;
                        }

                        if (!$dryRun) {
                            $stream = $public->readStream($path);
                            $private->writeStream($path, $stream);
                            if (is_resource($stream)) {
                                fclose($stream);
                            }

                            if ($private->exists($path) && $private->size($path) === $public->size($path)) {
                                $public->delete($path);
                            } else {
                                $this->warn("Gagal memverifikasi salinan: {$path} (berkas publik dipertahankan)");
                                continue;
                            }
                        }

                        $moved++;
                    }
                });
        }

        $verb = $dryRun ? 'akan dipindahkan' : 'dipindahkan';
        $this->info("{$moved} berkas {$verb} ke disk privat. {$missing} referensi berkas tidak ditemukan di kedua disk.");

        return Command::SUCCESS;
    }
}
