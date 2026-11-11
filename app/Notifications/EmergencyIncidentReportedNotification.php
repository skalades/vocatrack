<?php

namespace App\Notifications;

use App\Models\IncidentReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EmergencyIncidentReportedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public IncidentReport $incidentReport
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $studentName = $this->incidentReport->studentPkl?->student?->user?->name ?? 'Siswa PKL';
        $urgencyLabel = strtoupper($this->incidentReport->urgency);

        return [
            'type' => 'emergency_incident',
            'title' => "[{$urgencyLabel}] Laporan Kendala / SOS Siswa",
            'message' => "{$studentName}: \"{$this->incidentReport->subject}\"",
            'incident_id' => $this->incidentReport->id,
            'urgency' => $this->incidentReport->urgency,
            'url' => route('guru.incidents', ['incident_id' => $this->incidentReport->id]),
            'color' => in_array($this->incidentReport->urgency, ['high', 'critical']) ? 'rose' : 'amber',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
