<?php

/**
 * Script Pembersihan Data Presensi & Jurnal Uji Coba Hari Ini
 * Dijamin AMAN: Mengecualikan PT Sangga Buana dan PT Pertamina Geothermal
 * 
 * Cara Penggunaan:
 *   php clean_test_today.php
 *   atau
 *   php clean_test_today.php 2026-11-23
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalPhoto;
use App\Models\School;
use App\Models\StudentPkl;
use App\Services\StorageQuotaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

$date = $argv[1] ?? date('Y-m-d');

echo "\n=======================================================\n";
echo "   VOCATRACK - PEMBERSIHAN DATA UJI COBA ({$date})\n";
echo "=======================================================\n\n";

// 1. Identifikasi DU/DI yang DIKECUALIKAN (Wajib Aman & Terlindungi)
$excludedCompanies = Company::where(function ($q) {
    $q->where('name', 'LIKE', '%sangga buana%')
      ->orWhere('name', 'LIKE', '%pertamina geothermal%');
})->get(['id', 'name']);

echo "[1] Perusahaan yang DIKECUALIKAN (Aman, Tidak Dihapus):\n";
foreach ($excludedCompanies as $c) {
    echo "    - [ID: {$c->id}] {$c->name}\n";
}
$excludedCompanyIds = $excludedCompanies->pluck('id')->all();

// Ambil Student PKL ID dari perusahaan yang dikecualikan
$excludedSpklIds = StudentPkl::whereIn('company_id', $excludedCompanyIds)->pluck('id')->all();

// 2. Query data yang AKAN DIPERTAHANKAN (Verifikasi Proteksi Data)
$keptAttendances = Attendance::forDate($date)->whereIn('student_pkl_id', $excludedSpklIds)->count();
$keptJournals = Journal::forDate($date)->whereIn('student_pkl_id', $excludedSpklIds)->count();

echo "\n[2] Data RESMI yang DILINDUNGI pada tanggal {$date}:\n";
echo "    - Presensi Resmi Terlindungi : {$keptAttendances} data\n";
echo "    - Jurnal Resmi Terlindungi   : {$keptJournals} data\n";

// 3. Query data UJI COBA yang AKAN DIHAPUS
$attQuery = Attendance::forDate($date)->whereNotIn('student_pkl_id', $excludedSpklIds);
$journalQuery = Journal::forDate($date)->whereNotIn('student_pkl_id', $excludedSpklIds);

$attCount = (clone $attQuery)->count();
$journalCount = (clone $journalQuery)->count();
$journalIds = (clone $journalQuery)->pluck('id')->all();

$photos = JournalPhoto::whereIn('journal_id', $journalIds)->get();
$photoCount = $photos->count();

echo "\n[3] Data UJI COBA yang AKAN DIBERSIHKAN:\n";
echo "    - Presensi Uji Coba : {$attCount} data\n";
echo "    - Jurnal Uji Coba   : {$journalCount} data\n";
echo "    - Foto Jurnal       : {$photoCount} berkas\n\n";

if ($attCount === 0 && $journalCount === 0) {
    echo "Tidak ada data uji coba yang perlu dibersihkan untuk tanggal {$date}.\n";
    exit(0);
}

// 4. Hapus Berkas Fisik Foto dari Storage
echo "[4] Menghapus berkas foto fisik dari storage...\n";
$disk = Storage::disk('public');
$deletedFiles = 0;

foreach ($attQuery->get() as $att) {
    if ($att->checkin_photo_path && $disk->exists($att->checkin_photo_path)) {
        try {
            $disk->delete($att->checkin_photo_path);
            $deletedFiles++;
        } catch (\Throwable $e) {}
    }
    if ($att->checkout_photo_path && $disk->exists($att->checkout_photo_path)) {
        try {
            $disk->delete($att->checkout_photo_path);
            $deletedFiles++;
        } catch (\Throwable $e) {}
    }
}

foreach ($photos as $p) {
    if ($p->photo_path && $disk->exists($p->photo_path)) {
        try {
            $disk->delete($p->photo_path);
            $deletedFiles++;
        } catch (\Throwable $e) {}
    }
}
echo "    -> Berhasil menghapus {$deletedFiles} berkas gambar fisik dari disk server.\n";

// 5. Hapus Record Database secara Atomic Transaction
echo "[5] Menghapus data di basis data (Database Transaction)...\n";
DB::transaction(function () use ($journalIds, $journalQuery, $attQuery) {
    // Hapus foto jurnal
    JournalPhoto::whereIn('journal_id', $journalIds)->delete();
    // Hapus relasi pivot jika ada
    DB::table('journal_learning_objective')->whereIn('journal_id', $journalIds)->delete();
    DB::table('journal_approvals')->whereIn('journal_id', $journalIds)->delete();
    // Hapus jurnal
    $journalQuery->delete();
    // Hapus presensi
    $attQuery->delete();
});
echo "    -> Selesai! Seluruh record database uji coba telah dihapus.\n";

// 6. Hitung Ulang Kuota Storage Sekolah
echo "[6] Menghitung ulang kuota disk storage sekolah...\n";
$school = School::find(1);
if ($school) {
    app(StorageQuotaService::class)->calculateSchoolStorage($school);
    Cache::forget("school_storage_bytes_{$school->id}");
    echo "    -> Kuota storage SMK Negeri 2 Garut berhasil diperbarui.\n";
}

echo "\n=======================================================\n";
echo "✅ SUKSES! Pembersihan data uji coba selesai tanpa menyentuh data PT Sangga Buana & PT Pertamina Geothermal.\n";
echo "=======================================================\n\n";
