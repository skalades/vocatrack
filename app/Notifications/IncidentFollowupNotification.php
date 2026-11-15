<?php

namespace App\Notifications;

use App\Models\IncidentFollowup;
use App\Models\IncidentReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncidentFollowupNotification extends Notification
{
    use Queueable;

    public function __construct(
        public IncidentReport $report,
        public IncidentFollowup $followup,
        public ?string $newStatus = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $statusLabels = [
            'submitted' => 'Menunggu Tindak Lanjut',
            'in_progress' => 'Sedang Ditangani',
            'resolved' => 'Telah Diselesaikan',
            'dismissed' => 'Ditutup',
        ];

        $statusStr = $statusLabels[$this->report->status] ?? ucfirst($this->report->status);
        $title = "Tindak Lanjut Laporan Kendala: {$statusStr}";
        $message = "Catatan: \"{$this->followup->action_note}\"";

        return [
            'type' => 'incident_followup',
            'title' => $title,
            'message' => $message,
            'incident_report_id' => $this->report->id,
            'status' => $this->report->status,
            'url' => route('student.incidents'),
            'color' => $this->report->status === 'resolved' ? 'emerald' : 'indigo',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
