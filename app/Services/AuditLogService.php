<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    /**
     * Record an audit activity into database.
     */
    public static function log(
        string $action,
        string $module,
        string $description,
        ?School $school = null,
        ?User $user = null,
        ?array $before = null,
        ?array $after = null
    ): ?ActivityLog {
        try {
            $currentUser = $user ?: Auth::user();
            $schoolId = $school ? $school->id : ($currentUser?->school_id);

            return ActivityLog::create([
                'school_id' => $schoolId,
                'user_id' => $currentUser?->id,
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'user_agent' => substr(request()?->userAgent() ?? 'System / Console', 0, 255),
                'payload_before' => $before,
                'payload_after' => $after,
            ]);
        } catch (\Throwable $e) {
            Log::error("[AUDIT LOG ERROR] Failed to record audit log: " . $e->getMessage());
            return null;
        }
    }
}
