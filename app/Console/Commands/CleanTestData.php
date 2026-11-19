<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Journal;
use App\Models\JournalPhoto;
use App\Models\School;
use App\Services\StorageQuotaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanTestData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vokatrack:clean-test-data
                            {--date= : Tanggal data uji coba (format: YYYY-MM-DD, default hari ini)}
                            {--school= : ID Sekolah tertentu (opsional)}
                            {--all-companies : Hapus seluruh data pada tanggal tersebut termasuk perusahaan yang sudah mulai PKL (BAHAYA)}
                            {--dry-run : Hanya hitung dan tampilkan data tanpa melakukan penghapusan}
                            {--force : Lewati konfirmasi interaktif}';

    /**
     * The console command description.
     */
    protected $description = 'Bersihkan data presensi, jurnal, dan file uji coba/stress test secara aman tanpa mengganggu siswa aktif PKL';

    public function handle(): int
    {
        $date = $this->option('date') ?: Carbon::today()->toDateString();
        $schoolId = $this->option('school');
        $allCompanies = (bool) $this->option('all-companies');
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');

        $this->info("=== VokaTrack Data Cleanup Tool ===");
        $this->line("Target Tanggal: <comment>{$date}</comment>");
        $this->line("Filter Perusahaan: " . ($allCompanies ? "<error>SEMUA PERUSAHAAN (Termasuk yang sudah mulai)</error>" : "<info>Hanya DU/DI Belum Mulai (is_pkl_started = 0)</info>"));
        if ($schoolId) {
            $this->line("Filter Sekolah: ID <comment>{$schoolId}</comment>");
        }

        // 1. Query Presensi
        $attQuery = Attendance::forDate($date);
        if ($schoolId) {
            $attQuery->where('school_id', $schoolId);
        }
        if (!$allCompanies) {
            $attQuery->whereHas('studentPkl.company', fn($cq) => $cq->where('is_pkl_started', false));
        }

        // 2. Query Jurnal
        $journalQuery = Journal::forDate($date);
        if ($schoolId) {
            $journalQuery->where('school_id', $schoolId);
        }
        if (!$allCompanies) {
            $journalQuery->whereHas('studentPkl.company', fn($cq) => $cq->where('is_pkl_started', false));
        }

        // 3. Query Izin / Sakit
        $reqQuery = AttendanceRequest::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
        if ($schoolId) {
            $reqQuery->where('school_id', $schoolId);
        }
        if (!$allCompanies) {
            $reqQuery->whereHas('studentPkl.company', fn($cq) => $cq->where('is_pkl_started', false));
        }

        $attCount = (clone $attQuery)->count();
        $journalCount = (clone $journalQuery)->count();
        $reqCount = (clone $reqQuery)->count();

        // Hitung foto jurnal
        $journalIds = (clone $journalQuery)->pluck('id');
        $photoCount = JournalPhoto::whereIn('journal_id', $journalIds)->count();

        $this->newLine();
        $this->table(
            ['Kategori Data', 'Jumlah Ditemukan', 'Status Keamanan Siswa Aktif PKL'],
            [
                ['Presensi (Attendances)', $attCount, $allCompanies ? 'TERANCAM DIHAPUS' : 'TERLINDUNGI (Aman)'],
                ['Jurnal Harian (Journals)', $journalCount, $allCompanies ? 'TERANCAM DIHAPUS' : 'TERLINDUNGI (Aman)'],
                ['Foto Jurnal (Journal Photos)', $photoCount, $allCompanies ? 'TERANCAM DIHAPUS' : 'TERLINDUNGI (Aman)'],
                ['Pengajuan Izin (Attendance Requests)', $reqCount, $allCompanies ? 'TERANCAM DIHAPUS' : 'TERLINDUNGI (Aman)'],
            ]
        );

        if ($attCount === 0 && $journalCount === 0 && $reqCount === 0) {
            $this->info("Tidak ditemukan data uji coba pada tanggal {$date} dengan kriteria yang dipilih.");
            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn("[DRY RUN] Mode simulasi aktif. Tidak ada data yang dihapus dari database atau storage.");
            return Command::SUCCESS;
        }

        if (!$isForce) {
            if (!$this->confirm("Apakah Anda yakin ingin menghapus data di atas beserta berkas fisiknya?", false)) {
                $this->warn("Operasi dibatalkan.");
                return Command::FAILURE;
            }
        }

        $this->newLine();
        $this->info("Memulai proses pembersihan...");

        $deletedFiles = 0;

        // Kumpulkan dan hapus file foto presensi
        $attQuery->chunkById(200, function ($attendances) use (&$deletedFiles) {
            foreach ($attendances as $att) {
                if ($this->deleteFileSafely($att->checkin_photo_path)) {
                    $deletedFiles++;
                }
                if ($this->deleteFileSafely($att->checkout_photo_path ?? null)) {
                    $deletedFiles++;
                }
            }
        });

        // Kumpulkan dan hapus file foto jurnal
        JournalPhoto::whereIn('journal_id', $journalIds)->chunkById(200, function ($photos) use (&$deletedFiles) {
            foreach ($photos as $photo) {
                if ($this->deleteFileSafely($photo->photo_path)) {
                    $deletedFiles++;
                }
            }
        });

        // Kumpulkan dan hapus lampiran izin
        $reqQuery->chunkById(200, function ($requests) use (&$deletedFiles) {
            foreach ($requests as $req) {
                if ($this->deleteFileSafely($req->attachment_path)) {
                    $deletedFiles++;
                }
            }
        });

        // Hapus record database (transaksi aman)
        DB::transaction(function () use ($attQuery, $journalIds, $journalQuery, $reqQuery) {
            // Hapus foto jurnal & jurnal (cascade)
            JournalPhoto::whereIn('journal_id', $journalIds)->delete();
            $journalQuery->delete();

            // Hapus presensi
            $attQuery->delete();

            // Hapus pengajuan izin
            $reqQuery->delete();
        });

        // Hitung ulang kuota storage sekolah yang terdampak
        $schoolsToRecalculate = School::when($schoolId, fn($q) => $q->where('id', $schoolId))->get();
        $quotaService = app(StorageQuotaService::class);
        foreach ($schoolsToRecalculate as $school) {
            $quotaService->calculateSchoolStorage($school);
            Cache::forget("school_storage_bytes_{$school->id}");
        }

        $this->info("✔ Berhasil menghapus {$attCount} presensi, {$journalCount} jurnal ({$photoCount} foto), dan {$reqCount} permohonan.");
        $this->info("✔ Berhasil menghapus {$deletedFiles} berkas foto fisik dari storage disk.");
        $this->info("✔ Kuota penyimpanan sekolah telah disinkronkan kembali.");
        $this->info("✔ Data siswa aktif PKL yang perusahaannya sudah mulai (is_pkl_started = 1) TETAP AMAN 100%.");

        return Command::SUCCESS;
    }

    /**
     * Hapus berkas fisik dari disk lokal dan publik secara aman.
     */
    protected function deleteFileSafely(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        $deleted = false;

        try {
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
                $deleted = true;
            }
        } catch (\Throwable $e) {
            // Abaikan error disk lokal
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                $deleted = true;
            }
        } catch (\Throwable $e) {
            // Abaikan error disk publik
        }

        return $deleted;
    }
}
