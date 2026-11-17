<?php

namespace App\Notifications;

use App\Models\AttendanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AttendanceRequest $request,
        public string $status,
        public ?string $notes = null
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (!empty($notifiable->email) && (!method_exists($notifiable, 'wantsMailNotification') || $notifiable->wantsMailNotification('request_status'))) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function getNotificationType(): string
    {
        return 'request_status';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusText = ($this->status === 'approved') ? 'Disetujui' : 'Ditolak';
        $typeLabel = ucfirst(str_replace('_', ' ', $this->request->type));
        $startDateStr = \Carbon\Carbon::parse($this->request->start_date)->format('d M Y');

        $mail = (new MailMessage)
            ->subject("[VokaTrack] Permohonan {$typeLabel}: {$statusText}")
            ->line("Permohonan {$typeLabel} Anda untuk tanggal {$startDateStr} telah diproses dengan hasil: {$statusText}.");

        if ($this->notes) {
            $mail->line("Catatan pembimbing: \"{$this->notes}\"");
        }

        return $mail->action('Lihat Riwayat Presensi', url('/siswa/absensi'));
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
