<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $notificationService)
    {
        $user = Auth::user();
        $unreadCount = $notificationService->getUnreadCount($user);
        $notifications = $notificationService->getRecentNotifications($user, 20);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'unread_count' => $unreadCount,
                'notifications' => $notifications->map(fn($n) => [
                    'id' => $n->id,
                    'type' => $n->data['type'] ?? 'general',
                    'title' => $n->data['title'] ?? 'Pemberitahuan',
                    'message' => $n->data['message'] ?? '',
                    'url' => $n->data['url'] ?? '#',
                    'color' => $n->data['color'] ?? 'indigo',
                    'read_at' => $n->read_at ? $n->read_at->diffForHumans() : null,
                    'is_read' => $n->read_at !== null,
                    'created_at' => $n->created_at->diffForHumans(),
                ]),
            ]);
        }

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markAsRead(string $id, NotificationService $notificationService)
    {
        $notificationService->markAsRead(Auth::user(), $id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notifikasi telah ditandai dibaca.');
    }

    public function markAllAsRead(NotificationService $notificationService)
    {
        $count = $notificationService->markAllAsRead(Auth::user());

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'count' => $count]);
        }

        return back()->with('success', "{$count} notifikasi telah ditandai dibaca.");
    }
}
