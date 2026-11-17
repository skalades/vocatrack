<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\IncidentReport;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi darurat SOS — TIDAK di-queue agar terkirim langsung (real-time).
 * Jangan tambahkan ShouldQueue di sini.
 */
class EmergencyIncidentReportedNotification extends Notification
{

    public function __construct(
        public IncidentReport $incidentReport
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail', WhatsAppChannel::class];
    }

    public function getNotificationType(): string
    {
        return 'emergency_incident';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $studentName = $this->incidentReport->studentPkl?->student?->user?->name ?? 'Siswa PKL';
        $companyName = $this->incidentReport->studentPkl?->company?->name ?? 'DU/DI Terkait';
        $urgencyLabel = strtoupper($this->incidentReport->urgency);
        $url = route('guru.incidents', ['incident_id' => $this->incidentReport->id]);

        return (new MailMessage)
            ->error()
            ->subject("[VokaTrack SOS {$urgencyLabel}] Laporan Kendala Siswa: {$studentName}")
            ->greeting('Pemberitahuan Darurat PKL')
            ->line("Siswa {$studentName} di {$companyName} melaporkan insiden/kendala dengan urgensi {$urgencyLabel}.")
            ->line("Subjek: \"{$this->incidentReport->subject}\"")
            ->line("Deskripsi: {$this->incidentReport->description}")
            ->action('Tinjau & Tindak Lanjuti Laporan', $url)
            ->line('Harap segera memberikan arahan atau koordinasi lebih lanjut melalui portal guru.');
    }

    public function toWhatsApp(object $notifiable): string
    {
        $studentName = $this->incidentReport->studentPkl?->student?->user?->name ?? 'Siswa PKL';
        $companyName = $this->incidentReport->studentPkl?->company?->name ?? 'DU/DI Terkait';
        $urgencyLabel = strtoupper($this->incidentReport->urgency);
        $url = route('guru.incidents', ['incident_id' => $this->incidentReport->id]);

        return "🚨 *[VokaTrack — LAPORAN DARURAT SOS]*\n"
            . "Tingkat: *{$urgencyLabel}*\n"
            . "Siswa: {$studentName}\n"
            . "DU/DI: {$companyName}\n"
            . "Subjek: {$this->incidentReport->subject}\n"
            . "Keterangan: {$this->incidentReport->description}\n\n"
            . "Tinjau di portal:\n{$url}";
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
