<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\IncidentReport;
use App\Models\JournalPhoto;
use App\Models\StudentPkl;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penyimpanan & penyajian berkas pribadi siswa (selfie presensi, foto jurnal,
 * lampiran izin/sakit, lampiran laporan kendala).
 *
 * Berkas disimpan di disk privat (tidak dapat diakses lewat /storage) dan hanya
 * disajikan melalui route yang memeriksa hak akses per siswa PKL.
 */
class SecureFileService
{
    /** Disk privat untuk berkas sensitif (root: storage/app/private). */
    public const DISK = 'local';

    /** Disk lama tempat berkas sebelum migrasi; hanya dipakai sebagai fallback baca. */
    public const LEGACY_DISK = 'public';

    /** Tipe berkas yang dapat disajikan => [model, kolom path]. */
    public const TYPES = [
        'presensi' => [Attendance::class, 'checkin_photo_path'],
        'jurnal' => [JournalPhoto::class, 'photo_path'],
        'permohonan' => [AttendanceRequest::class, 'attachment_path'],
        'kendala' => [IncidentReport::class, 'attachment_path'],
    ];

    protected const INLINE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    public function putContents(string $path, string $contents): string
    {
        Storage::disk(self::DISK)->put($path, $contents);

        return $path;
    }

    public function storeUploadedFile(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');
        $path = trim($directory, '/') . '/' . Str::random(32) . '.' . $extension;
        Storage::disk(self::DISK)->putFileAs(dirname($path), $file, basename($path));

        return $path;
    }

    public function delete(?string $path): void
    {
        if (!$path) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
        Storage::disk(self::LEGACY_DISK)->delete($path);
    }

    public function exists(?string $path): bool
    {
        return $path && ($this->diskFor($path) !== null);
    }

    public function size(?string $path): int
    {
        $disk = $path ? $this->diskFor($path) : null;

        return $disk ? (int) Storage::disk($disk)->size($path) : 0;
    }

    /**
     * Disk tempat berkas berada (privat diutamakan, lalu disk lama), atau null.
     */
    public function diskFor(string $path): ?string
    {
        if (Storage::disk(self::DISK)->exists($path)) {
            return self::DISK;
        }

        if (Storage::disk(self::LEGACY_DISK)->exists($path)) {
            return self::LEGACY_DISK;
        }

        return null;
    }

    /**
     * Resolusi model + path berkas berdasarkan tipe & id. Global scope tenant tetap berlaku.
     *
     * @return array{0: Model, 1: string, 2: StudentPkl}|null
     */
    public function resolve(string $type, int|string $id): ?array
    {
        if (!isset(self::TYPES[$type])) {
            return null;
        }

        [$modelClass, $column] = self::TYPES[$type];
        $model = $modelClass::query()->find($id);
        if (!$model || empty($model->{$column})) {
            return null;
        }

        $spkl = $type === 'jurnal'
            ? $model->journal?->studentPkl
            : $model->studentPkl;

        if (!$spkl) {
            return null;
        }

        return [$model, $model->{$column}, $spkl];
    }

    /**
     * Apakah pengguna berhak melihat berkas milik siswa PKL ini.
     */
    public function canAccess(User $user, StudentPkl $spkl, string $type): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if (!$user->school_id || (int) $user->school_id !== (int) $spkl->school_id) {
            return false;
        }

        return match ($user->role) {
            'admin-sekolah', 'kepala-sekolah' => true,
            'siswa' => $user->student && (int) $user->student->id === (int) $spkl->student_id,
            'guru-pembimbing' => $user->teacher && (int) $user->teacher->id === (int) $spkl->teacher_id,
            // Laporan kendala/SOS sengaja tidak terlihat oleh pihak DU/DI.
            'pembimbing-dudi' => $type !== 'kendala'
                && $user->supervisor
                && (int) $user->supervisor->company_id === (int) $spkl->company_id,
            default => false,
        };
    }

    /**
     * Sajikan berkas dengan header aman. Gambar ditampilkan inline, selain itu diunduh.
     */
    public function respond(string $path): Response
    {
        $disk = $this->diskFor($path);
        abort_if($disk === null, 404, 'Berkas tidak ditemukan.');

        $absolute = Storage::disk($disk)->path($path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolute) ?: 'application/octet-stream';

        $headers = [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
        ];

        if (in_array($mime, self::INLINE_MIME_TYPES, true)) {
            return response()->file($absolute, $headers);
        }

        return response()->download($absolute, basename($path), $headers);
    }
}
