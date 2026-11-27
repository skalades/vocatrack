<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\AttendanceEscalation;
use App\Notifications\Concerns\SendsWebPush;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class AttendanceEscalationNotification extends Notification
{
    use SendsWebPush;

    public function __construct(
        public AttendanceEscalation $escalation
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', WhatsAppChannel::class];

        return $this->appendWebPushChannel($channels, $notifiable, 'attendance_escalation');
    }

    public function getNotificationType(): string
    {
        return 'attendance_escalation';
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        $payload = $this->toArray($notifiable);

        return $this->buildWebPushMessage(
            title: $payload['title'],
            body: $payload['message'],
            url: $payload['url'],
            tag: 'escalation-' . $this->escalation->student_pkl_id . '-' . $this->escalation->date->toDateString(),
            actionTitle: 'Buka Monitoring',
            requireInteraction: true
        );
    }

    public function toWhatsApp(object $notifiable): string
    {
        $this->escalation->loadMissing([
            'studentPkl.student.user.profile',
            'studentPkl.student.class.major',
            'studentPkl.company',
            'escalatedBy',
        ]);

        $spkl = $this->escalation->studentPkl;
        $studentName = $spkl?->student?->user?->name ?? 'Siswa PKL';
        $className = $spkl?->student?->class?->name ?? '-';
        $majorName = $spkl?->student?->class?->major?->name ?? '-';
        $studentPhone = $spkl?->student?->user?->wa_number ?: ($spkl?->student?->user?->profile?->phone ?? '-');
        $companyName = $spkl?->company?->name ?? 'Mitra DU/DI';
        $dateLabel = $this->escalation->date->isoFormat('dddd, D MMMM Y');
        $missingText = implode(', ', $this->escalation->missing_labels);
        $adminName = $this->escalation->escalatedBy?->name ?? 'Admin Sekolah';

        $text = "🔔 *[VokaTrack — Pengingat Kepatuhan Siswa PKL]*\n"
            . "Yth. Bapak/Ibu Guru Pembimbing,\n\n"
            . "Mohon bantuannya untuk mengingatkan siswa bimbingan berikut:\n"
            . "• *Siswa:* {$studentName} ({$className} - {$majorName})\n"
            . "• *Kontak Siswa:* {$studentPhone}\n"
            . "• *DU/DI:* {$companyName}\n"
            . "• *Tanggal:* {$dateLabel}\n"
            . "• *Status:* {$missingText}\n";

        if (!empty($this->escalation->note)) {
            $text .= "• *Catatan Admin ({$adminName}):* {$this->escalation->note}\n";
        }

        $text .= "\nSilakan cek detailnya di: " . route('guru.monitoring', ['date' => $this->escalation->date->toDateString()]);

        return $text;
    }

    public function toArray(object $notifiable): array
    {
        $this->escalation->loadMissing([
            'studentPkl.student.user',
            'studentPkl.student.class.major',
            'studentPkl.company',
            'escalatedBy',
        ]);

        $spkl = $this->escalation->studentPkl;
        $studentName = $spkl?->student?->user?->name ?? 'Siswa PKL';
        $className = $spkl?->student?->class?->name ?? '-';
        $companyName = $spkl?->company?->name ?? 'DU/DI';
        $dateStr = $this->escalation->date->toDateString();
        $missingText = implode(', ', $this->escalation->missing_labels);
        $notePart = !empty($this->escalation->note) ? " — Catatan: \"{$this->escalation->note}\"" : '';

        return [
            'type' => 'attendance_escalation',
            'title' => "Eskalasi Admin: {$studentName} ({$missingText})",
            'message' => "{$studentName} ({$className}) di {$companyName} pada {$dateStr}: {$missingText}. Mohon ingatkan siswa yang bersangkutan.{$notePart}",
            'escalation_id' => $this->escalation->id,
            'student_pkl_id' => $this->escalation->student_pkl_id,
            'date' => $dateStr,
            'missing_types' => $this->escalation->missing_types,
            'url' => route('guru.monitoring', ['date' => $dateStr]),
            'color' => 'amber',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
