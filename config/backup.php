<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Penyimpanan Cadangan Off-Site
    |--------------------------------------------------------------------------
    |
    | Nama disk (lihat config/filesystems.php) tempat salinan cadangan otomatis
    | diunggah. Kosongkan untuk hanya menyimpan cadangan di server lokal
    | (tidak disarankan untuk produksi). Disk "backup_s3" mendukung layanan
    | S3-compatible seperti Cloudflare R2, Backblaze B2, atau IDCloudHost OSS.
    |
    */

    'offsite_disk' => env('BACKUP_OFFSITE_DISK'),

    'offsite_path' => trim(env('BACKUP_OFFSITE_PATH', 'vokatrack/database'), '/'),

    /*
    |--------------------------------------------------------------------------
    | Jadwal
    |--------------------------------------------------------------------------
    */

    'schedule_time' => env('BACKUP_SCHEDULE_TIME', '02:00'),

    /*
    |--------------------------------------------------------------------------
    | Retensi
    |--------------------------------------------------------------------------
    |
    | Lokal: cadangan otomatis yang lebih tua dari N hari dihapus dari server.
    | Off-site: skema GFS — simpan cadangan terbaru untuk setiap N hari, N minggu,
    | dan N bulan terakhir. Cadangan manual & snapshot pra-restore tidak pernah
    | dihapus otomatis.
    |
    */

    'local_keep_days' => (int) env('BACKUP_LOCAL_KEEP_DAYS', 7),

    'retention' => [
        'daily' => (int) env('BACKUP_KEEP_DAILY', 7),
        'weekly' => (int) env('BACKUP_KEEP_WEEKLY', 4),
        'monthly' => (int) env('BACKUP_KEEP_MONTHLY', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Prefix Nama Berkas
    |--------------------------------------------------------------------------
    |
    | Hanya berkas dengan prefix di "managed_prefixes" yang dikelola retensi
    | otomatis (masing-masing prefix dihitung terpisah).
    |
    */

    'auto_prefix' => 'auto_backup_vokatrack',

    'managed_prefixes' => [
        'auto_backup_vokatrack',
        'pre_deploy_vokatrack',
    ],

];
