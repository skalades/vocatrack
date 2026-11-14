<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreBroadcastRequest;
use App\Models\PlatformAnnouncement;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BroadcastController extends Controller
{
    public function broadcasts()
    {
        $broadcasts = PlatformAnnouncement::with('creator')->latest()->paginate(15);
        $totalBroadcasts = PlatformAnnouncement::count();
        $activeBroadcasts = PlatformAnnouncement::where('is_active', true)->count();

        return view('superadmin.broadcasts', compact('broadcasts', 'totalBroadcasts', 'activeBroadcasts'));
    }

    public function storeBroadcast(StoreBroadcastRequest $request)
    {
        $announcement = PlatformAnnouncement::create([
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'target_role' => $request->target_role,
            'is_active' => true,
            'is_dismissible' => $request->boolean('is_dismissible', true),
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at,
            'created_by' => Auth::id(),
        ]);

        AuditLogService::log(
            'broadcast_create',
            'announcement',
            "Siaran pengumuman global '{$announcement->title}' tipe [{$announcement->type}] diterbitkan."
        );

        return back()->with('success', "Siaran pengumuman global '{$announcement->title}' berhasil dipublikasikan.");
    }

    public function updateBroadcast(StoreBroadcastRequest $request, PlatformAnnouncement $broadcast)
    {
        $broadcast->update([
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'target_role' => $request->target_role,
            'is_dismissible' => $request->boolean('is_dismissible', true),
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at,
        ]);

        AuditLogService::log(
            'broadcast_update',
            'announcement',
            "Siaran pengumuman global '{$broadcast->title}' diperbarui."
        );

        return back()->with('success', "Pengumuman global '{$broadcast->title}' berhasil diperbarui.");
    }

    public function toggleBroadcast(PlatformAnnouncement $broadcast)
    {
        $broadcast->update(['is_active' => !$broadcast->is_active]);

        $status = $broadcast->is_active ? 'diaktifkan' : 'dinonaktifkan';

        AuditLogService::log(
            'broadcast_toggle',
            'announcement',
            "Status siaran '{$broadcast->title}' diubah menjadi {$status}."
        );

        return back()->with('success', "Status siaran '{$broadcast->title}' berhasil {$status}.");
    }

    public function destroyBroadcast(PlatformAnnouncement $broadcast)
    {
        $title = $broadcast->title;
        $broadcast->delete();

        AuditLogService::log(
            'broadcast_delete',
            'announcement',
            "Siaran pengumuman global '{$title}' dihapus."
        );

        return back()->with('success', "Pengumuman global '{$title}' berhasil dihapus.");
    }
}
