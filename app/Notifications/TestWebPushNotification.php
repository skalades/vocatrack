<?php

namespace App\Notifications;

use App\Notifications\Concerns\SendsWebPush;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TestWebPushNotification extends Notification
{
    use SendsWebPush;

    public function via(object $notifiable): array
    {
        return $this->isWebPushAvailableFor($notifiable) ? [WebPushChannel::class] : [];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        $url = ($notifiable->role ?? null) === 'guru-pembimbing'
            ? '/guru/dashboard'
            : '/siswa/beranda';

        return $this->buildWebPushMessage(
            title: 'Notifikasi VokaTrack Aktif',
            body: 'Perangkat Anda siap menerima pembaruan penting secara langsung meski aplikasi ditutup.',
            url: $url,
            tag: 'vokatrack-push-test',
            actionTitle: 'Buka Aplikasi'
        );
    }
}
