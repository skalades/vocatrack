<?php

namespace App\Notifications;

use App\Models\Announcement;
use App\Notifications\Concerns\SendsWebPush;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushMessage;

class AnnouncementPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsWebPush;

    public function __construct(public Announcement $announcement)
    {
    }

    public function via(object $notifiable): array
    {
        return $this->appendWebPushChannel(['database'], $notifiable, 'announcement');
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        $isUrgent = $this->announcement->category === 'penting';
        $prefix = $isUrgent ? '[Penting] ' : '';

        return $this->buildWebPushMessage(
            title: $prefix . $this->announcement->title,
            body: Str::limit(strip_tags((string) $this->announcement->content), 140),
            url: '/siswa/pengumuman',
            tag: 'announcement-' . $this->announcement->id,
            actionTitle: 'Baca Pengumuman',
            requireInteraction: $isUrgent
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'announcement',
            'title' => 'Pengumuman: ' . $this->announcement->title,
            'message' => Str::limit(strip_tags((string) $this->announcement->content), 120),
            'announcement_id' => $this->announcement->id,
            'url' => '/siswa/pengumuman',
            'color' => $this->announcement->category === 'penting' ? 'rose' : 'indigo',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
