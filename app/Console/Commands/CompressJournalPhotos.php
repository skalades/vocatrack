<?php

namespace App\Console\Commands;

use App\Models\JournalPhoto;
use App\Models\School;
use App\Services\ImageCompressionService;
use App\Services\SecureFileService;
use App\Services\StorageQuotaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CompressJournalPhotos extends Command
{
    protected $signature = 'vokatrack:compress-journal-photos 
                            {--school= : Batasi kompresi untuk ID sekolah tertentu} 
                            {--dry-run : Hanya tampilkan audit ukuran tanpa mengubah berkas} 
                            {--threshold=300 : Ambang batas ukuran minimal berkas dalam KB untuk dikompresi}';

    protected $description = 'Audit dan kompresi foto jurnal siswa yang berukuran besar untuk menghemat ruang disk dan kuota sekolah';

    public function handle(ImageCompressionService $compressor, SecureFileService $files): int
    {
        $schoolId = $this->option('school');
        $dryRun = (bool) $this->option('dry-run');
        $thresholdKb = (int) $this->option('threshold');
        $thresholdBytes = $thresholdKb * 1024;

        $this->info('Memulai audit dan kompresi foto jurnal...');
        if ($dryRun) {
            $this->warn('Mode: DRY-RUN (tidak ada berkas atau database yang diubah)');
        }

        $query = JournalPhoto::withoutGlobalScopes()
            ->whereNotNull('photo_path')
            ->where('photo_path', '!=', '');

        if ($schoolId) {
            $query->whereHas('journal', fn($q) => $q->withoutGlobalScopes()->where('school_id', $schoolId));
        }

        $totalScanned = 0;
        $totalCompressed = 0;
        $totalSkipped = 0;
        $bytesBefore = 0;
        $bytesAfter = 0;

        $query->chunkById(100, function ($photos) use (
            $compressor,
            $files,
            $dryRun,
            $thresholdBytes,
            &$totalScanned,
            &$totalCompressed,
            &$totalSkipped,
            &$bytesBefore,
            &$bytesAfter
        ) {
            foreach ($photos as $photo) {
                $totalScanned++;
                $path = $photo->photo_path;
                $disk = $files->diskFor($path);

                if (!$disk || !Storage::disk($disk)->exists($path)) {
                    $totalSkipped++;
                    continue;
                }

                $size = Storage::disk($disk)->size($path);
                if ($size < $thresholdBytes) {
                    $totalSkipped++;
                    continue;
                }

                $content = Storage::disk($disk)->get($path);
                $ext = pathinfo($path, PATHINFO_EXTENSION);

                $result = $compressor->compressBinary($content, $ext);
                if (!$result['compressed'] || $result['size'] >= $size) {
                    $totalSkipped++;
                    continue;
                }

                $savedBytes = $size - $result['size'];
                $bytesBefore += $size;
                $bytesAfter += $result['size'];
                $totalCompressed++;

                $this->line(sprintf(
                    '[%s] Foto #%d: %s -> %s (hemat %s)',
                    $dryRun ? 'AUDIT' : 'KOMPRESI',
                    $photo->id,
                    $this->formatBytes($size),
                    $this->formatBytes($result['size']),
                    $this->formatBytes($savedBytes)
                ));

                if (!$dryRun) {
                    $targetPath = $path;
                    if (!empty($result['extension']) && strtolower($ext) !== strtolower($result['extension'])) {
                        $targetPath = preg_replace('/\.[^.]+$/', '.' . $result['extension'], $path) ?: ($path . '.' . $result['extension']);
                    }

                    Storage::disk($disk)->put($targetPath, $result['data']);
                    if ($targetPath !== $path) {
                        $photo->update(['photo_path' => $targetPath]);
                        Storage::disk($disk)->delete($path);
                    }

                    // Rekam pengurangan kuota sekolah
                    $journal = $photo->journal()->withoutGlobalScopes()->first();
                    if ($journal && $journal->school_id) {
                        $school = School::find($journal->school_id);
                        if ($school) {
                            app(StorageQuotaService::class)->recordDeletion($school, $savedBytes);
                        }
                    }
                }
            }
        });

        $this->newLine();
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Total Foto Dipindai', $totalScanned],
                ['Foto Perlu/Berhasil Dikompresi', $totalCompressed],
                ['Foto Dilewati (< threshold/optimal)', $totalSkipped],
                ['Total Ukuran Sebelum', $this->formatBytes($bytesBefore)],
                ['Total Ukuran Sesudah', $this->formatBytes($bytesAfter)],
                ['Total Ruang Dihemat', $this->formatBytes(max(0, $bytesBefore - $bytesAfter))],
            ]
        );

        $this->info('Audit foto jurnal selesai.');

        return self::SUCCESS;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
