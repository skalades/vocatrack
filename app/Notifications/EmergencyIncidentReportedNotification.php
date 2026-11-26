<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\IncidentReport;
use App\Notifications\Concerns\SendsWebPush;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Notifikasi darurat SOS — TIDAK di-queue agar terkirim langsung (real-time).
 * Jangan tambahkan ShouldQueue di sini.
 */
class EmergencyIncidentReportedNotification extends Notification
{
    use SendsWebPush;

    public function __construct(
        public IncidentReport $incidentReport
    ) {}

    public function via(object $notifiable): array
    {
        // Notifikasi darurat SOS: pesan WhatsApp dikhususkan HANYA untuk Guru Pembimbing
        // Admin sekolah menerima notifikasi in-app database, kecuali jika bertindak sebagai fallback darurat
        $role = $notifiable->role ?? null;
        $channels = ($role === 'guru-pembimbing' || !empty($notifiable->is_emergency_fallback))
            ? ['database', WhatsAppChannel::class]
            : ['database'];

        return $this->appendWebPushChannel($channels, $notifiable, 'emergency_incident');
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        $payload = $this->toArray($notifiable);
        $isAdmin = ($notifiable->role ?? null) === 'admin-sekolah';

        return $this->buildWebPushMessage(
            title: $payload['title'],
            body: $payload['message'],
            url: $isAdmin ? '/admin-sekolah/incidents' : '/guru/incidents?incident_id=' . $this->incidentReport->id,
            tag: 'sos-incident-' . $this->incidentReport->id,
            actionTitle: 'Tinjau SOS',
            requireInteraction: true
        );
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
        $teacherName = $this->incidentReport->studentPkl?->teacher?->user?->name;

        $isAdmin = ($notifiable->role ?? null) === 'admin-sekolah';
        $url = $isAdmin
            ? route('adminsekolah.incidents')
            : route('guru.incidents', ['incident_id' => $this->incidentReport->id]);

        $text = "🚨 *[VokaTrack — LAPORAN DARURAT SOS]*\n"
            . "Tingkat: *{$urgencyLabel}*\n"
            . "Siswa: {$studentName}\n"
            . "DU/DI: {$companyName}\n"
            . "Guru Pembimbing: " . ($teacherName ?: '⚠️ Belum dialokasikan') . "\n"
            . "Subjek: {$this->incidentReport->subject}\n"
            . "Keterangan: {$this->incidentReport->description}\n\n"
            . "Tinjau di portal:\n{$url}";

        if ($isAdmin && empty($teacherName)) {
            $text .= "\n\n⚠️ *Perhatian Admin Sekolah:* Siswa ini belum dialokasikan Guru Pembimbing di sistem. Mohon segera ambil tindakan darurat.";
        }

        return $text;
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
