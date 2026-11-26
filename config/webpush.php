<?php

use NotificationChannels\WebPush\PushSubscription;

return [

    /**
     * Kunci autentikasi VAPID untuk Web Push (Android Chrome/Edge/Samsung Internet & iOS 16.4+ PWA).
     * Wajib stabil dan tidak berubah setelah siswa berlangganan notifikasi.
     */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:noreply@smkn2garut.sch.id'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'pem_file' => env('VAPID_PEM_FILE'),
    ],

    'model' => PushSubscription::class,

    'table_name' => env('WEBPUSH_DB_TABLE', 'push_subscriptions'),

    /**
     * Gunakan null agar memakai koneksi database default aplikasi (kompatibel SQLite test & PostgreSQL/MySQL produksi).
     */
    'database_connection' => env('WEBPUSH_DB_CONNECTION'),

    'client_options' => [],

    'automatic_padding' => env('WEBPUSH_AUTOMATIC_PADDING', true),

];
