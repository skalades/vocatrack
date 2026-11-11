<?php

namespace App\Notifications;

use App\Models\Journal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class JournalStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Journal $journal,
        public string $status,
        public ?string $notes = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
