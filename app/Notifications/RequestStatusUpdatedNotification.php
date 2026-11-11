<?php

namespace App\Notifications;

use App\Models\AttendanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RequestStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public AttendanceRequest $request,
        public string $status,
        public ?string $notes = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $statusText = ($this->status === 'approved') ? 'Disetujui' : 'Ditolak';
        $typeLabel = ucfirst(str_replace('_', ' ', $this->request->type));
        $startDateStr = \Carbon\Carbon::parse($this->request->start_date)->format('d M Y');

        $message = $this->notes
            ? "Catatan: \"{$this->notes}\""
            : "Permohonan {$typeLabel} untuk tanggal {$startDateStr} telah {$statusText}.";

        return [
            'type' => 'request_status',
            'title' => "Permohonan {$typeLabel} {$statusText}",
            'message' => $message,
            'request_id' => $this->request->id,
            'status' => $this->status,
            'url' => '/siswa/absensi',
            'color' => $this->status === 'approved' ? 'emerald' : 'rose',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
