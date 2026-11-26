<?php

namespace App\Notifications\Concerns;

use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

trait SendsWebPush
{
    /**
     * Tambahkan channel WebPush hanya bila VAPID aktif, user mengizinkan channel ini,
     * dan user memiliki minimal satu subscription perangkat tersimpan.
     */
    protected function appendWebPushChannel(array $channels, object $notifiable, string $type = 'general'): array
    {
        if (! $this->isWebPushAvailableFor($notifiable, $type)) {
            return $channels;
        }

        $channels[] = WebPushChannel::class;

        return $channels;
    }

    protected function isWebPushAvailableFor(object $notifiable, string $type = 'general'): bool
    {
        if (blank(config('webpush.vapid.public_key')) || blank(config('webpush.vapid.private_key'))) {
            return false;
        }

        if (! method_exists($notifiable, 'pushSubscriptions')) {
            return false;
        }

        if (method_exists($notifiable, 'wantsChannel') && ! $notifiable->wantsChannel('push', $type)) {
            return false;
        }

        return $notifiable->pushSubscriptions()->exists();
    }

    /**
     * Susun payload WebPush yang dioptimalkan untuk Android (lock screen / notification bar)
     * dan iOS 16.4+ PWA standalone.
     */
    protected function buildWebPushMessage(
        string $title,
        string $body,
        string $url = '/siswa/beranda',
        string $tag = 'vokatrack-notif',
        string $actionTitle = 'Buka Aplikasi',
        bool $requireInteraction = false
    ): WebPushMessage {
        return (new WebPushMessage)
            ->title($title)
            ->body($body)
            ->icon('/icons/icon-192.png')
            ->badge('/icons/icon-192.png')
            ->tag($tag)
            ->renotify()
            ->requireInteraction($requireInteraction)
            ->vibrate([150, 80, 150])
            ->data([
                'url' => $url,
                'tag' => $tag,
            ])
            ->action($actionTitle, 'open')
            ->options([
                'TTL' => 86400,
                'urgency' => $requireInteraction ? 'high' : 'normal',
            ]);
    }
}
