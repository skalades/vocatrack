<?php

namespace App\Notifications;

use App\Models\SchoolRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Masuk ke inbox (lonceng notifikasi) Super Admin setiap ada permohonan demo baru dari landing page.
 */
class DemoRequestSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public SchoolRequest $schoolRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $req = $this->schoolRequest;
        $students = $req->estimated_students ? " · ±{$req->estimated_students} siswa" : '';

        return [
            'type' => 'demo_request_submitted',
            'title' => "Permohonan demo baru: {$req->school_name}",
            'message' => "NPSN {$req->npsn} · {$req->contact_name} ({$req->phone}){$students}",
            'school_request_id' => $req->id,
            'url' => route('superadmin.school-requests', ['status' => 'pending'], false),
            'color' => 'emerald',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
