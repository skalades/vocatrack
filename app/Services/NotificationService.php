<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send notification to a specific user.
     */
    public function sendToUser(User $user, Notification $notification): void
    {
        $user->notify($notification);
    }

    /**
     * Get count of unread notifications for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Get list of recent notifications (both read and unread).
     */
    public function getRecentNotifications(User $user, int $limit = 15): Collection
    {
        return $user->notifications()->take($limit)->get();
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->where('id', $notificationId)->first();
        if ($notification && !$notification->read_at) {
            $notification->markAsRead();
            return true;
        }
        return false;
    }

    /**
     * Mark all notifications of a user as read.
     */
    public function markAllAsRead(User $user): int
    {
        $unread = $user->unreadNotifications;
        $count = $unread->count();
        $unread->markAsRead();
        return $count;
    }
}
