<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\JournalPhoto;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class StorageQuotaService
{
    /**
     * Calculate total bytes consumed by a single school tenant.
     * Uses chunking to prevent memory overload on large datasets.
     */
    public function calculateSchoolStorage(School $school): int
    {
        $totalBytes = 0;
        $disk = Storage::disk('public');

        // 1. School Logo & Stamp
        $totalBytes += $this->getFileSizeSafe($disk, $school->logo_path);
        $totalBytes += $this->getFileSizeSafe($disk, $school->stamp_path);

        // 2. Student attendance photos (chunked query)
        Attendance::where('school_id', $school->id)
            ->whereNotNull('checkin_photo_path')
            ->select('id', 'checkin_photo_path')
            ->chunk(250, function ($attendances) use ($disk, &$totalBytes) {
                foreach ($attendances as $att) {
                    $totalBytes += $this->getFileSizeSafe($disk, $att->checkin_photo_path);
                }
            });

        // 3. Attendance Request attachments (chunked query)
        AttendanceRequest::where('school_id', $school->id)
            ->whereNotNull('attachment_path')
            ->select('id', 'attachment_path')
            ->chunk(250, function ($requests) use ($disk, &$totalBytes) {
                foreach ($requests as $req) {
                    $totalBytes += $this->getFileSizeSafe($disk, $req->attachment_path);
                }
            });

        // 4. Journal photos (chunked query)
        JournalPhoto::whereHas('journal', function ($q) use ($school) {
            $q->where('school_id', $school->id);
        })
        ->select('id', 'photo_path')
        ->chunk(250, function ($photos) use ($disk, &$totalBytes) {
            foreach ($photos as $photo) {
                $totalBytes += $this->getFileSizeSafe($disk, $photo->photo_path);
            }
        });

        // 5. Update school storage_used_bytes cache & database safely
        try {
            if (Schema::hasColumn('schools', 'storage_used_bytes')) {
                $school->update(['storage_used_bytes' => $totalBytes]);
            }
        } catch (\Throwable $e) {
            Log::warning("[STORAGE QUOTA] Could not persist storage_used_bytes column for school {$school->id}: " . $e->getMessage());
        }

        Cache::put("school_storage_bytes_{$school->id}", $totalBytes, now()->addHours(6));

        return $totalBytes;
    }

    /**
     * Increment school storage usage in real-time when new media is uploaded.
     */
    public function recordUpload(School $school, int $fileSizeBytes): void
    {
        if ($fileSizeBytes <= 0) {
            return;
        }

        try {
            if (Schema::hasColumn('schools', 'storage_used_bytes')) {
                $school->increment('storage_used_bytes', $fileSizeBytes);
                Cache::put("school_storage_bytes_{$school->id}", $school->storage_used_bytes, now()->addHours(6));
            }
        } catch (\Throwable $e) {
            Log::warning("[STORAGE QUOTA] Could not increment storage_used_bytes for school {$school->id}: " . $e->getMessage());
        }
    }

    /**
     * Decrement school storage usage in real-time when media is deleted.
     */
    public function recordDeletion(School $school, int $fileSizeBytes): void
    {
        if ($fileSizeBytes <= 0) {
            return;
        }

        try {
            if (Schema::hasColumn('schools', 'storage_used_bytes')) {
                $current = (int) ($school->storage_used_bytes ?? 0);
                $newBytes = max(0, $current - $fileSizeBytes);
                $school->update(['storage_used_bytes' => $newBytes]);
                Cache::put("school_storage_bytes_{$school->id}", $newBytes, now()->addHours(6));
            }
        } catch (\Throwable $e) {
            Log::warning("[STORAGE QUOTA] Could not decrement storage_used_bytes for school {$school->id}: " . $e->getMessage());
        }
    }

    /**
     * Safely get file size from disk without crashing on missing or unreadable files.
     */
    protected function getFileSizeSafe($disk, ?string $path): int
    {
        if (empty($path)) {
            return 0;
        }

        try {
            if ($disk->exists($path)) {
                return (int) $disk->size($path);
            }
        } catch (\Throwable $e) {
            // Silently skip if file is missing, corrupted, or has permission restriction
        }

        return 0;
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
