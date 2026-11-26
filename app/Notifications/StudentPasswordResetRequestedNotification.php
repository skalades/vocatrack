<?php

namespace App\Notifications;

use App\Channels\WhatsAppChannel;
use App\Models\Student;
use App\Models\StudentPasswordResetRequest;
use App\Notifications\Concerns\SendsWebPush;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class StudentPasswordResetRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsWebPush;

    public function __construct(
        public Student $student,
        public StudentPasswordResetRequest $resetRequest
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        if (!empty($notifiable->wa_number)) {
            $channels[] = WhatsAppChannel::class;
        }

        return $this->appendWebPushChannel($channels, $notifiable, 'password_reset_request');
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        $payload = $this->toArray($notifiable);
        $nisn = $this->student->nisn ?? '';

        return $this->buildWebPushMessage(
            title: $payload['title'],
            body: $payload['message'],
            url: '/guru/monitoring' . ($nisn ? '?q=' . urlencode($nisn) : ''),
            tag: 'pw-reset-' . $this->resetRequest->id,
            actionTitle: 'Buka Monitoring'
        );
    }

    public function getNotificationType(): string
    {
        return 'password_reset_request';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $studentName = $this->student->user?->name ?? 'Siswa';
        $nisn = $this->student->nisn ?? '-';
        $url = route('guru.monitoring', ['q' => $nisn]);

        return (new MailMessage)
            ->subject("[VokaTrack] Permohonan Reset Kata Sandi Siswa: {$studentName}")
            ->greeting("Halo Bapak/Ibu {$notifiable->name},")
            ->line("Siswa bimbingan Anda, **{$studentName}** (NISN: {$nisn}, Email: {$this->resetRequest->email}), telah mengajukan permohonan reset kata sandi akun VokaTrack.")
            ->line("Sebagai guru pembimbing, Anda memiliki wewenang untuk mereset kata sandi siswa tersebut secara langsung melalui menu Monitoring Siswa.")
            ->action('Reset Kata Sandi Siswa', $url)
            ->line('Jika Anda merasa tidak membimbing siswa ini atau terdapat kekeliruan, Anda dapat mengabaikan email ini.');
    }

    public function toWhatsApp(object $notifiable): string
    {
        $studentName = $this->student->user?->name ?? 'Siswa';
        $nisn = $this->student->nisn ?? '-';
        $url = route('guru.monitoring', ['q' => $nisn]);

        return "🔐 *[VokaTrack — Permohonan Reset Sandi Siswa]*\n\n"
            . "Halo Bapak/Ibu {$notifiable->name},\n"
            . "Siswa bimbingan Anda mengajukan permohonan reset kata sandi:\n"
            . "• Nama: *{$studentName}*\n"
            . "• NISN: {$nisn}\n"
            . "• Email: {$this->resetRequest->email}\n\n"
            . "Silakan lakukan reset sandi melalui tautan portal berikut:\n{$url}";
    }

    public function toArray(object $notifiable): array
    {
        $studentName = $this->student->user?->name ?? 'Siswa';
        $nisn = $this->student->nisn ?? '-';

        return [
            'type' => 'student_password_reset_request',
            'title' => 'Permintaan Reset Sandi Siswa',
            'message' => "Siswa {$studentName} (NISN: {$nisn}) mengajukan permohonan reset kata sandi.",
            'student_id' => $this->student->id,
            'reset_request_id' => $this->resetRequest->id,
            'url' => route('guru.monitoring', ['q' => $nisn]),
            'color' => 'amber',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
