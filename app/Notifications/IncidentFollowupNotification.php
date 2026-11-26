<?php

namespace App\Notifications;

use App\Models\IncidentFollowup;
use App\Models\IncidentReport;
use App\Notifications\Concerns\SendsWebPush;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class IncidentFollowupNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsWebPush;

    public function __construct(
        public IncidentReport $report,
        public IncidentFollowup $followup,
        public ?string $newStatus = null
    ) {}

    public function via(object $notifiable): array
    {
        return $this->appendWebPushChannel(['database'], $notifiable, 'incident_followup');
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        $payload = $this->toArray($notifiable);

        return $this->buildWebPushMessage(
            title: $payload['title'],
            body: $payload['message'],
            url: ($notifiable->role ?? null) === 'siswa' ? '/siswa/kendala' : '/guru/incidents',
            tag: 'incident-' . $this->report->id,
            actionTitle: 'Lihat Tindak Lanjut',
            requireInteraction: true
        );
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
        $isTeacher = ($notifiable->role ?? null) === 'guru';
        $title = $isTeacher 
            ? "Update Siswa: Laporan {$this->report->subject}"
            : "Tindak Lanjut Laporan Kendala: {$statusStr}";
        $message = "Catatan: \"{$this->followup->action_note}\"";

        return [
            'type' => 'incident_followup',
            'title' => $title,
            'message' => $message,
            'incident_report_id' => $this->report->id,
            'status' => $this->report->status,
            'url' => $isTeacher ? route('guru.incidents') : route('student.incidents'),
            'color' => $this->report->status === 'resolved' ? 'emerald' : 'indigo',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
