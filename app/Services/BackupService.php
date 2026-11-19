<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Journal;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class BackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    public function backupDirectory(): string
    {
        return $this->backupDir;
    }

    /**
     * Seluruh tabel domain yang dicadangkan, diurutkan sesuai dependensi foreign key
     * (induk lebih dulu). Restore menghapus data dengan urutan terbalik lalu mengisi
     * ulang dengan urutan ini, sehingga tetap aman walau pemeriksaan FK tidak bisa
     * dimatikan (mis. PostgreSQL tanpa hak superuser).
     */
    public const BACKUP_TABLES = [
        'schools',
        'school_settings',
        'subscription_plans',
        'school_subscriptions',
        'invoices',
        'platform_settings',
        'users',
        'profiles',
        'majors',
        'learning_objectives',
        'classes',
        'students',
        'teachers',
        'major_teacher',
        'companies',
        'company_major',
        'supervisors',
        'pkl_periods',
        'student_pkl',
        'attendances',
        'attendance_requests',
        'journals',
        'journal_photos',
        'journal_learning_objective',
        'journal_approvals',
        'assessments',
        'announcements',
        'announcement_reads',
        'school_holidays',
        'incident_reports',
        'incident_followups',
        'supervision_visits',
        'certificates',
        'notifications',
        'activity_logs',
        'platform_announcements',
        'school_requests',
        'student_password_reset_requests',
    ];

    /**
     * Tabel infrastruktur/ephemeral yang sengaja tidak dicadangkan.
     */
    public const EXCLUDED_TABLES = [
        'migrations',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
        'password_reset_tokens',
    ];

    /**
     * Tabel yang tetap dicadangkan tetapi TIDAK ditimpa saat restore.
     */
    public const PRESERVED_ON_RESTORE = [
        'activity_logs',
    ];

    public const FORMAT_NAME = 'vokatrack-backup';
    public const FORMAT_VERSION = 2;
    public const FILE_EXTENSION = 'vtbak';

    protected const CHUNK_SIZE = 500;

    /**
     * Buat snapshot terenkripsi seluruh tabel domain.
     *
     * Format berkas (.vtbak): baris pertama berisi header JSON polos, setiap baris
     * berikutnya adalah satu chunk {t: tabel, r: baris[]} yang dienkripsi dengan APP_KEY.
     * Ditulis secara streaming sehingga tidak menampung seluruh database di memori.
     */
    public function createDatabaseBackup(string $prefix = 'backup_vokatrack'): array
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        $filename = "{$prefix}_{$timestamp}_" . Str::lower(Str::random(6)) . '.' . self::FILE_EXTENSION;
        $filePath = "{$this->backupDir}/{$filename}";

        $schema = DB::getSchemaBuilder();
        $tables = array_values(array_filter(self::BACKUP_TABLES, fn ($t) => $schema->hasTable($t)));

        $handle = fopen($filePath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException("Tidak dapat menulis berkas cadangan {$filename}.");
        }

        $rowCounts = [];

        try {
            $header = [
                'format' => self::FORMAT_NAME,
                'version' => self::FORMAT_VERSION,
                'app' => 'VokaTrack Cloud',
                'created_at' => Carbon::now()->toIso8601String(),
                'driver' => DB::getDriverName(),
                'encrypted' => true,
                'key_fingerprint' => $this->keyFingerprint(),
                'tables' => $tables,
            ];
            fwrite($handle, json_encode($header) . "\n");

            foreach ($tables as $table) {
                $rowCounts[$table] = 0;
                $query = DB::table($table);
                $this->applyDeterministicOrder($query, $table);

                $query->chunk(self::CHUNK_SIZE, function ($chunk) use ($handle, $table, &$rowCounts) {
                    $rows = $chunk->map(fn ($item) => (array) $item)->all();
                    $rowCounts[$table] += count($rows);
                    $payload = json_encode(['t' => $table, 'r' => $rows], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
                    fwrite($handle, Crypt::encryptString($payload) . "\n");
                });
            }
        } catch (\Throwable $e) {
            fclose($handle);
            File::delete($filePath);
            throw $e;
        }

        fclose($handle);
        @chmod($filePath, 0600);

        return [
            'filename' => $filename,
            'path' => $filePath,
            'size' => File::size($filePath),
            'size_formatted' => round(File::size($filePath) / 1024, 2) . ' KB',
            'created_at' => Carbon::now(),
            'tables_count' => count($tables),
            'row_counts' => $rowCounts,
        ];
    }

    /**
     * Pulihkan database dari berkas cadangan (.vtbak terenkripsi, atau .json format lama).
     *
     * Berkas divalidasi penuh terlebih dahulu, lalu snapshot pengaman otomatis dibuat
     * sehingga restore yang keliru masih dapat dibatalkan.
     */
    public function restoreDatabaseBackup(string $filename): array
    {
        $safeFilename = basename($filename);
        $filePath = "{$this->backupDir}/{$safeFilename}";

        if (!File::exists($filePath)) {
            throw new \RuntimeException("Berkas cadangan {$safeFilename} tidak ditemukan.");
        }

        $extension = strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION));
        if (!in_array($extension, [self::FILE_EXTENSION, 'json'], true)) {
            throw new \RuntimeException("Berkas {$safeFilename} bukan snapshot database yang dapat dipulihkan.");
        }

        // Validasi penuh (dekripsi + parsing) sebelum menyentuh data apa pun.
        $tablesInBackup = $extension === self::FILE_EXTENSION
            ? $this->validateEncryptedBackup($filePath, $safeFilename)
            : array_keys($this->readLegacyBackup($filePath, $safeFilename));

        $safetySnapshot = $this->createDatabaseBackup('pre_restore_safety');

        $schema = DB::getSchemaBuilder();
        $tablesInBackup = array_values(array_filter($tablesInBackup, fn ($t) => $schema->hasTable($t)));

        // Urutkan sesuai dependensi FK; tabel tak dikenal (dari backup lama) di akhir.
        $ordered = array_values(array_unique(array_merge(
            array_values(array_intersect(self::BACKUP_TABLES, $tablesInBackup)),
            array_values(array_diff($tablesInBackup, self::BACKUP_TABLES))
        )));

        // Jejak audit tidak pernah ditimpa: log setelah snapshot (termasuk aksi restore ini)
        // harus tetap utuh untuk kebutuhan forensik.
        $ordered = array_values(array_diff($ordered, self::PRESERVED_ON_RESTORE));

        // Akun super admin saat ini dipertahankan agar restore tidak mengunci operator
        // (mis. kata sandi kembali ke versi lama atau akun hilang dari snapshot).
        $preservedSuperAdmins = in_array('users', $ordered, true)
            ? DB::table('users')->where('role', 'super-admin')->get()->map(fn ($row) => (array) $row)->all()
            : [];

        $this->withForeignKeyChecksDisabled(function () use ($ordered, $extension, $filePath, $safeFilename, $preservedSuperAdmins) {
            DB::transaction(function () use ($ordered, $extension, $filePath, $safeFilename, $preservedSuperAdmins) {
                foreach (array_reverse($ordered) as $table) {
                    DB::table($table)->delete();
                }

                $columnCache = [];
                $insert = function (string $table, array $rows) use ($ordered, &$columnCache) {
                    if (empty($rows) || !in_array($table, $ordered, true)) {
                        return;
                    }
                    // Abaikan kolom yang sudah tidak ada di skema saat ini.
                    $columns = $columnCache[$table] ??= array_flip(Schema::getColumnListing($table));
                    $rows = array_map(fn ($row) => array_intersect_key((array) $row, $columns), $rows);
                    foreach (array_chunk($rows, 200) as $batch) {
                        DB::table($table)->insert($batch);
                    }
                };

                if ($extension === self::FILE_EXTENSION) {
                    $this->eachEncryptedChunk($filePath, $safeFilename, fn (string $table, array $rows) => $insert($table, $rows));
                } else {
                    $legacy = $this->readLegacyBackup($filePath, $safeFilename);
                    foreach ($ordered as $table) {
                        $insert($table, $legacy[$table] ?? []);
                    }
                }

                if (!empty($preservedSuperAdmins)) {
                    DB::table('users')->where('role', 'super-admin')->delete();
                    foreach ($preservedSuperAdmins as $row) {
                        DB::table('users')
                            ->where('id', $row['id'])
                            ->orWhere('email', $row['email'])
                            ->delete();
                        DB::table('users')->insert($row);
                    }
                }

                $this->resetPostgresSequences($ordered);
            });
        });

        return [
            'filename' => $safeFilename,
            'restored_at' => Carbon::now(),
            'tables_count' => count($ordered),
            'safety_snapshot' => $safetySnapshot['filename'],
        ];
    }

    /**
     * Sidik jari pendek APP_KEY untuk mendeteksi ketidakcocokan kunci saat restore.
     */
    protected function keyFingerprint(): string
    {
        return substr(hash('sha256', (string) config('app.key')), 0, 16);
    }

    /**
     * Validasi header serta seluruh chunk terenkripsi. Mengembalikan daftar tabel.
     */
    protected function validateEncryptedBackup(string $filePath, string $safeFilename): array
    {
        $header = $this->readEncryptedHeader($filePath, $safeFilename);
        $this->eachEncryptedChunk($filePath, $safeFilename, function () {});

        return $header['tables'];
    }

    protected function readEncryptedHeader(string $filePath, string $safeFilename): array
    {
        $handle = fopen($filePath, 'rb');
        $line = $handle ? fgets($handle) : false;
        if ($handle) {
            fclose($handle);
        }

        $header = $line !== false ? json_decode($line, true) : null;
        if (!is_array($header) || ($header['format'] ?? null) !== self::FORMAT_NAME || !isset($header['tables']) || !is_array($header['tables'])) {
            throw new \RuntimeException("Format berkas cadangan {$safeFilename} tidak valid.");
        }

        if (($header['key_fingerprint'] ?? null) !== $this->keyFingerprint()) {
            throw new \RuntimeException("Berkas cadangan {$safeFilename} dienkripsi dengan APP_KEY yang berbeda dan tidak dapat dipulihkan di server ini.");
        }

        return $header;
    }

    /**
     * Iterasi setiap chunk terenkripsi secara streaming: $callback(string $table, array $rows).
     */
    protected function eachEncryptedChunk(string $filePath, string $safeFilename, callable $callback): void
    {
        $header = $this->readEncryptedHeader($filePath, $safeFilename);
        $allowedTables = array_flip($header['tables']);

        $handle = fopen($filePath, 'rb');
        fgets($handle); // lewati header

        try {
            $lineNo = 1;
            while (($line = fgets($handle)) !== false) {
                $lineNo++;
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                try {
                    $chunk = json_decode(Crypt::decryptString($line), true, 512, JSON_THROW_ON_ERROR);
                } catch (\Throwable $e) {
                    throw new \RuntimeException("Berkas cadangan {$safeFilename} rusak pada baris {$lineNo}.");
                }

                if (!isset($chunk['t'], $chunk['r']) || !is_array($chunk['r']) || !isset($allowedTables[$chunk['t']])) {
                    throw new \RuntimeException("Berkas cadangan {$safeFilename} memuat data tidak valid pada baris {$lineNo}.");
                }

                $callback($chunk['t'], $chunk['r']);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Baca berkas cadangan format lama (.json polos, v1). Mengembalikan [tabel => baris[]].
     */
    protected function readLegacyBackup(string $filePath, string $safeFilename): array
    {
        $backupData = json_decode(File::get($filePath), true);

        if (!is_array($backupData) || !isset($backupData['tables']) || !is_array($backupData['tables'])) {
            throw new \RuntimeException("Format berkas cadangan {$safeFilename} tidak valid.");
        }

        $tables = $backupData['tables'];

        // Backup v1 memakai nama tabel keliru "school_classes" (tabel sebenarnya "classes").
        if (isset($tables['school_classes']) && !isset($tables['classes'])) {
            $tables['classes'] = $tables['school_classes'];
        }
        unset($tables['school_classes']);

        foreach (self::EXCLUDED_TABLES as $excluded) {
            unset($tables[$excluded]);
        }

        return $tables;
    }

    /**
     * Matikan pemeriksaan foreign key selama callback (MySQL & SQLite). Dijalankan di luar
     * transaksi karena PRAGMA SQLite diabaikan di dalam transaksi. PostgreSQL mengandalkan
     * urutan hapus/isi berdasarkan dependensi.
     */
    protected function withForeignKeyChecksDisabled(callable $callback): void
    {
        $driver = DB::getDriverName();
        $isMysql = in_array($driver, ['mysql', 'mariadb'], true);

        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        try {
            $callback();
        } finally {
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }
        }
    }

    /**
     * Setelah insert dengan ID eksplisit, sequence PostgreSQL disetel ulang agar insert
     * berikutnya tidak bentrok dengan primary key.
     */
    protected function resetPostgresSequences(array $tables): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($tables as $table) {
            $sequence = DB::selectOne('SELECT pg_get_serial_sequence(?, ?) AS seq', [$table, 'id'])?->seq;
            if (!$sequence) {
                continue;
            }
            $quoted = '"' . str_replace('"', '""', $table) . '"';
            DB::statement(
                "SELECT setval(?, COALESCE((SELECT MAX(id) FROM {$quoted}), 1), (SELECT MAX(id) FROM {$quoted}) IS NOT NULL)",
                [$sequence]
            );
        }
    }

    /**
     * Terapkan urutan deterministik untuk chunking (mendukung tabel tanpa kolom 'id', misal pivot table).
     */
    protected function applyDeterministicOrder(\Illuminate\Database\Query\Builder $query, string $table): \Illuminate\Database\Query\Builder
    {
        $columns = Schema::getColumnListing($table);

        if (in_array('id', $columns, true)) {
            return $query->orderBy('id');
        }

        if (!empty($columns)) {
            foreach ($columns as $column) {
                $query->orderBy($column);
            }
            return $query;
        }

        return $query;
    }

    /**
     * List all database backups.
     */
    public function listBackups(): array
    {
        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            if (in_array($file->getExtension(), [self::FILE_EXTENSION, 'json', 'sql', 'zip'], true)) {
                $backups[] = [
                    'filename' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'size_formatted' => round($file->getSize() / 1024, 2) . ' KB',
                    'created_at' => Carbon::createFromTimestamp($file->getMTime()),
                ];
            }
        }

        // Sort latest first
        usort($backups, fn($a, $b) => $b['created_at'] <=> $a['created_at']);

        return $backups;
    }

    /**
     * Delete a backup file.
     */
    public function deleteBackup(string $filename): bool
    {
        $filePath = "{$this->backupDir}/" . basename($filename);
        if (File::exists($filePath)) {
            return File::delete($filePath);
        }
        return false;
    }

    /**
     * Generate complete Tenant Offboarding Zip Archive for a given School.
     */
    public function exportSchoolDataArchive(School $school): string
    {
        $tempDir = storage_path('app/temp_offboarding_' . $school->id . '_' . time());
        File::makeDirectory($tempDir, 0755, true);

        // 1. School Metadata JSON
        $meta = [
            'npsn' => $school->npsn,
            'name' => $school->name,
            'email' => $school->email,
            'phone' => $school->phone,
            'address' => $school->address,
            'exported_at' => Carbon::now()->toIso8601String(),
            'settings' => $school->settings()->pluck('value', 'key')->toArray(),
        ];
        File::put("{$tempDir}/informasi_sekolah.json", json_encode($meta, JSON_PRETTY_PRINT));

        // 2. Students & Classes CSV
        $studentsFile = fopen("{$tempDir}/data_siswa.csv", 'w');
        fprintf($studentsFile, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        fputcsv($studentsFile, ['NIS', 'NISN', 'Nama Siswa', 'Kelas', 'Jurusan', 'Email', 'Status']);
        Student::with(['class.major', 'user'])
            ->whereHas('class', fn($q) => $q->where('school_id', $school->id))
            ->chunk(500, function ($students) use ($studentsFile) {
                foreach ($students as $s) {
                    fputcsv($studentsFile, [
                        $s->nis,
                        $s->nisn,
                        $s->name,
                        $s->class?->name ?? '-',
                        $s->class?->major?->name ?? '-',
                        $s->user?->email ?? '-',
                        $s->user?->is_active ? 'Aktif' : 'Nonaktif',
                    ]);
                }
            });
        fclose($studentsFile);

        // 3. Attendances CSV
        $attendancesFile = fopen("{$tempDir}/rekap_presensi.csv", 'w');
        fprintf($attendancesFile, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($attendancesFile, ['Tanggal', 'Nama Siswa', 'NISN', 'Status Hadir', 'Waktu Masuk', 'Waktu Pulang', 'Jarak GPS (m)', 'Catatan']);
        Attendance::with(['studentPkl.student.user'])
            ->where('school_id', $school->id)
            ->latest('date')
            ->chunk(500, function ($attendances) use ($attendancesFile) {
                foreach ($attendances as $att) {
                    $student = $att->studentPkl?->student;
                    fputcsv($attendancesFile, [
                        $att->date?->format('Y-m-d') ?? (is_string($att->date) ? $att->date : '-'),
                        $student?->user?->name ?? $student?->name ?? '-',
                        $student?->nisn ?? '-',
                        $att->status,
                        $att->checkin_time?->format('H:i:s') ?? (is_string($att->checkin_time) ? $att->checkin_time : '-'),
                        $att->checkout_time?->format('H:i:s') ?? (is_string($att->checkout_time) ? $att->checkout_time : '-'),
                        $att->distance_meters ?? '-',
                        $att->notes ?? $att->checkout_notes ?? '-',
                    ]);
                }
            });
        fclose($attendancesFile);

        // 4. Journals CSV
        $journalsFile = fopen("{$tempDir}/rekap_jurnal.csv", 'w');
        fprintf($journalsFile, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($journalsFile, ['Tanggal', 'Nama Siswa', 'Status Approval', 'Uraian Kegiatan', 'Catatan Guru', 'Catatan DUDI']);
        Journal::with('studentPkl.student.user')
            ->where('school_id', $school->id)
            ->latest('date')
            ->chunk(500, function ($journals) use ($journalsFile) {
                foreach ($journals as $j) {
                    $student = $j->studentPkl?->student;
                    fputcsv($journalsFile, [
                        $j->date?->format('Y-m-d') ?? (is_string($j->date) ? $j->date : '-'),
                        $student?->user?->name ?? $student?->name ?? '-',
                        $j->status,
                        $j->content ?? $j->activity_description ?? '-',
                        $j->teacher_notes ?? '-',
                        $j->dudi_notes ?? '-',
                    ]);
                }
            });
        fclose($journalsFile);

        // 5. Companies CSV
        $companiesFile = fopen("{$tempDir}/mitra_dudi.csv", 'w');
        fprintf($companiesFile, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($companiesFile, ['Nama Perusahaan', 'Alamat', 'Kontak / Telepon', 'PIC Industri']);
        $companies = Company::where('school_id', $school->id)->get();
        foreach ($companies as $c) {
            fputcsv($companiesFile, [
                $c->name,
                $c->address,
                $c->phone,
                $c->contact_person ?? '-',
            ]);
        }
        fclose($companiesFile);

        // 6. Zip everything into one archive
        $zipFilename = 'arsip_offboarding_' . str_replace(' ', '_', strtolower($school->name)) . '_' . date('Ymd_His') . '.zip';
        $zipPath = "{$this->backupDir}/{$zipFilename}";

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $files = File::files($tempDir);
            foreach ($files as $file) {
                $zip->addFile($file->getPathname(), $file->getFilename());
            }
            $zip->close();
        }

        // Clean temp dir
        File::deleteDirectory($tempDir);

        return $zipPath;
    }
}
