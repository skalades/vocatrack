<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemAlertNotification;
use Illuminate\Support\Facades\Log;

/**
 * Kirim peringatan operasional ke seluruh super admin aktif. Kegagalan pengiriman
 * (mis. SMTP mati) tidak boleh menggagalkan proses pemanggil.
 */
class SystemAlertService
{
    public function notifySuperAdmins(string $event, string $title, string $message, string $url, string $color = 'rose'): void
    {
        Log::warning("[SYSTEM ALERT] {$title}: {$message}");

        $admins = User::where('role', 'super-admin')->where('is_active', true)->get();

        foreach ($admins as $admin) {
            try {
                $admin->notify(new SystemAlertNotification($event, $title, $message, $url, $color));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
