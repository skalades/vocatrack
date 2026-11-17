<?php

namespace App\Notifications;

use App\Models\Journal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JournalStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Journal $journal,
        public string $status,
        public ?string $notes = null
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (!empty($notifiable->email) && (!method_exists($notifiable, 'wantsMailNotification') || $notifiable->wantsMailNotification('journal_status'))) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function getNotificationType(): string
    {
        return 'journal_status';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dateStr = \Carbon\Carbon::parse($this->journal->date)->format('d M Y');
        $statusLabel = match ($this->status) {
            'approved' => 'Disetujui',
            'revision' => 'Perlu Revisi',
            'review_guru' => 'Diverifikasi DU/DI',
            default => ucfirst($this->status),
        };

        $mail = (new MailMessage)
            ->subject("[VokaTrack] Pembaruan Status Jurnal PKL: {$statusLabel}")
            ->line("Jurnal kegiatan PKL Anda untuk tanggal {$dateStr} telah diperbarui statusnya menjadi: {$statusLabel}.");

        if ($this->notes) {
            $mail->line("Catatan pembimbing: \"{$this->notes}\"");
        }

        return $mail->action('Lihat Jurnal Saya', url('/siswa/jurnal'));
    }

    public function toArray(object $notifiable): array
    {
        $statusLabels = [
            'review_guru' => 'Lolos Verifikasi DU/DI (Menunggu Guru)',
            'approved' => 'Telah Disetujui (Final Approved)',
            'revision' => 'Memerlukan Revisi / Perbaikan',
        ];

        $title = 'Status Jurnal: ' . ($statusLabels[$this->status] ?? ucfirst($this->status));
        $dateStr = \Carbon\Carbon::parse($this->journal->date)->format('d M Y');
        $message = $this->status === 'revision' && $this->notes
            ? "Catatan: \"{$this->notes}\""
            : "Jurnal kegiatan tanggal {$dateStr} telah diverifikasi.";

        return [
            'type' => 'journal_status',
            'title' => $title,
            'message' => $message,
            'journal_id' => $this->journal->id,
            'status' => $this->status,
            'url' => '/siswa/jurnal',
            'color' => $this->status === 'revision' ? 'amber' : 'emerald',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
