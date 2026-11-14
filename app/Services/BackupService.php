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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Create a structured snapshot backup of key platform tables.
     */
    public function createDatabaseBackup(): array
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        $filename = "backup_vokatrack_{$timestamp}.json";
        $filePath = "{$this->backupDir}/{$filename}";

        $tables = [
            'schools',
            'school_settings',
            'subscription_plans',
            'school_subscriptions',
            'invoices',
            'users',
            'profiles',
            'majors',
            'school_classes',
            'students',
            'teachers',
            'companies',
            'supervisors',
            'pkl_periods',
            'student_pkl',
            'attendances',
            'journals',
            'assessments',
            'platform_settings',
            'platform_announcements',
            'activity_logs',
        ];

        $backupData = [
            'app' => 'VokaTrack Cloud',
            'version' => '1.3.0',
            'created_at' => Carbon::now()->toIso8601String(),
            'tables' => [],
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $backupData['tables'][$table] = DB::table($table)->get()->toArray();
            }
        }

        File::put($filePath, json_encode($backupData, JSON_PRETTY_PRINT));

        return [
            'filename' => $filename,
            'path' => $filePath,
            'size' => File::size($filePath),
            'size_formatted' => round(File::size($filePath) / 1024, 2) . ' KB',
            'created_at' => Carbon::now(),
        ];
    }

    /**
     * List all database backups.
     */
    public function listBackups(): array
    {
        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'json' || $file->getExtension() === 'sql' || $file->getExtension() === 'zip') {
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
        $students = Student::with(['class.major', 'user'])
            ->whereHas('class', fn($q) => $q->where('school_id', $school->id))
            ->get();
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
        fclose($studentsFile);

        // 3. Attendances CSV
        $attendancesFile = fopen("{$tempDir}/rekap_presensi.csv", 'w');
        fprintf($attendancesFile, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($attendancesFile, ['Tanggal', 'Nama Siswa', 'NISN', 'Status Hadir', 'Waktu Masuk', 'Waktu Pulang', 'Jarak GPS (m)', 'Catatan']);
        $studentUserIds = User::where('school_id', $school->id)->where('role', 'siswa')->pluck('id');
        $attendances = Attendance::with('user.student')
            ->whereIn('user_id', $studentUserIds)
            ->latest('date')
            ->get();
        foreach ($attendances as $att) {
            fputcsv($attendancesFile, [
                $att->date?->format('Y-m-d') ?? '-',
                $att->user?->name ?? '-',
                $att->user?->student?->nisn ?? '-',
                $att->status,
                $att->checkin_time?->format('H:i:s') ?? '-',
                $att->checkout_time?->format('H:i:s') ?? '-',
                $att->distance_meters ?? '-',
                $att->notes ?? '-',
            ]);
        }
        fclose($attendancesFile);

        // 4. Journals CSV
        $journalsFile = fopen("{$tempDir}/rekap_jurnal.csv", 'w');
        fprintf($journalsFile, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($journalsFile, ['Tanggal', 'Nama Siswa', 'Status Approval', 'Uraian Kegiatan', 'Catatan Guru', 'Catatan DUDI']);
        $studentPklIds = StudentPkl::whereHas('student.class', fn($q) => $q->where('school_id', $school->id))->pluck('id');
        $journals = Journal::with('studentPkl.student')
            ->whereIn('student_pkl_id', $studentPklIds)
            ->latest('date')
            ->get();
        foreach ($journals as $j) {
            fputcsv($journalsFile, [
                $j->date?->format('Y-m-d') ?? '-',
                $j->studentPkl?->student?->name ?? '-',
                $j->status,
                $j->content,
                $j->teacher_notes ?? '-',
                $j->dudi_notes ?? '-',
            ]);
        }
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
