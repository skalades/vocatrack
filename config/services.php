<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | URL heartbeat (mis. Healthchecks.io / BetterStack / UptimeRobot) yang di-ping setiap
    | kali cron berhasil atau gagal. Kosongkan untuk menonaktifkan.
    */
    'heartbeat' => [
        'evaluate_absence' => env('HEARTBEAT_EVALUATE_ABSENCE_URL'),
        'evaluate_subscriptions' => env('HEARTBEAT_EVALUATE_SUBSCRIPTIONS_URL'),
        'backup' => env('HEARTBEAT_BACKUP_URL'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'provider' => env('WHATSAPP_GATEWAY_PROVIDER', 'custom'),
        'token' => env('WHATSAPP_GATEWAY_TOKEN', 'vokatrack-secret-key-2026'),
        'url' => env('WHATSAPP_GATEWAY_URL', 'http://127.0.0.1:3005/send-message'),
        'enabled' => env('WHATSAPP_GATEWAY_ENABLED', true),
    ],

];
