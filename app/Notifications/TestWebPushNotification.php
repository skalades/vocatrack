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
        return $this->buildWebPushMessage(
            title: 'Notifikasi VokaTrack Aktif',
            body: 'Perangkat Anda siap menerima pembaruan jurnal, status izin, dan pengumuman PKL meski aplikasi ditutup.',
            url: '/siswa/beranda',
            tag: 'vokatrack-push-test',
            actionTitle: 'Buka Beranda'
        );
    }
}
