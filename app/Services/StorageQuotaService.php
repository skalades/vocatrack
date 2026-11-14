<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\JournalPhoto;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StorageQuotaService
{
    /**
     * Calculate total bytes consumed by a single school tenant.
     */
    public function calculateSchoolStorage(School $school): int
    {
        $totalBytes = 0;
        $disk = Storage::disk('public');

        // 1. School Logo & Stamp
        if ($school->logo_path && $disk->exists($school->logo_path)) {
            $totalBytes += $disk->size($school->logo_path);
        }
        if ($school->stamp_path && $disk->exists($school->stamp_path)) {
            $totalBytes += $disk->size($school->stamp_path);
        }

        // 2. Student attendance photos
        $studentUserIds = User::where('school_id', $school->id)
            ->where('role', 'siswa')
            ->pluck('id');

        if ($studentUserIds->isNotEmpty()) {
            $attendancePhotos = Attendance::whereIn('user_id', $studentUserIds)
                ->whereNotNull('checkin_photo_path')
                ->pluck('checkin_photo_path');

            foreach ($attendancePhotos as $photo) {
                if ($photo && $disk->exists($photo)) {
                    $totalBytes += $disk->size($photo);
                }
            }

            // 3. Journal photos
            $studentIds = Student::whereIn('user_id', $studentUserIds)->pluck('id');
            if ($studentIds->isNotEmpty()) {
                $journalPhotos = JournalPhoto::whereHas('journal.studentPkl', function ($q) use ($studentIds) {
                    $q->whereIn('student_id', $studentIds);
                })->pluck('photo_path');

                foreach ($journalPhotos as $photo) {
                    if ($photo && $disk->exists($photo)) {
                        $totalBytes += $disk->size($photo);
                    }
                }
            }
        }

        // 4. Update school storage_used_bytes cache & database
        $school->update(['storage_used_bytes' => $totalBytes]);
        Cache::put("school_storage_bytes_{$school->id}", $totalBytes, now()->addHours(6));

        return $totalBytes;
    }

    /**
     * Recalculate storage for all active schools.
     */
    public function recalculateAllSchools(): array
    {
        $schools = School::all();
        $totalPlatformBytes = 0;

        foreach ($schools as $school) {
            $totalPlatformBytes += $this->calculateSchoolStorage($school);
        }

        return [
            'total_schools' => $schools->count(),
            'total_platform_bytes' => $totalPlatformBytes,
            'total_platform_mb' => round($totalPlatformBytes / (1024 * 1024), 2),
        ];
    }

    /**
     * Check if a school can upload media of given size.
     */
    public function canUpload(School $school, int $newFileSizeBytes = 0): bool
    {
        $currentBytes = $school->storage_used_bytes ?? 0;
        $maxBytes = $school->storage_quota_mb * 1024 * 1024;

        return ($currentBytes + $newFileSizeBytes) <= $maxBytes;
    }

    /**
     * Get platform-wide storage statistics.
     */
    public function getPlatformStorageStats(): array
    {
        $totalBytes = (int) School::sum('storage_used_bytes');
        $totalMb = round($totalBytes / (1024 * 1024), 2);
        $totalGb = round($totalMb / 1024, 2);

        $topSchools = School::orderByDesc('storage_used_bytes')
            ->take(5)
            ->get();

        return [
            'total_bytes' => $totalBytes,
            'total_mb' => $totalMb,
            'total_gb' => $totalGb,
            'top_schools' => $topSchools,
        ];
    }
}
