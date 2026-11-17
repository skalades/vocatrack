<?php

namespace App\Services;

use Carbon\CarbonImmutable;

/**
 * Kebijakan retensi Grandfather-Father-Son (GFS) untuk berkas cadangan otomatis.
 *
 * Dari daftar nama berkas, tentukan mana yang dipertahankan: cadangan terbaru di
 * setiap hari (N hari terakhir), setiap minggu ISO (N minggu terakhir), dan setiap
 * bulan (N bulan terakhir). Berkas yang namanya tidak memuat stempel waktu tidak
 * pernah dihapus.
 */
class BackupRetentionPolicy
{
    public function __construct(
        protected int $keepDaily = 7,
        protected int $keepWeekly = 4,
        protected int $keepMonthly = 3,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (int) config('backup.retention.daily', 7),
            (int) config('backup.retention.weekly', 4),
            (int) config('backup.retention.monthly', 3),
        );
    }

    /**
     * Ambil stempel waktu dari nama berkas "{prefix}_{Ymd_His}_{acak}.vtbak".
     */
    public static function timestampFromFilename(string $filename): ?CarbonImmutable
    {
        if (!preg_match('/_(\d{8}_\d{6})_[a-z0-9]+\.[a-z0-9]+$/i', basename($filename), $m)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Ymd_His', $m[1]);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  string[]  $filenames
     * @return string[]  Berkas yang boleh dihapus.
     */
    public function filesToDelete(array $filenames, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        $dated = [];
        foreach ($filenames as $file) {
            $ts = self::timestampFromFilename($file);
            if ($ts !== null) {
                $dated[$file] = $ts;
            }
        }

        // Terbaru lebih dulu, agar berkas pertama per periode adalah yang terbaru.
        arsort($dated);

        $keep = [];
        $this->keepNewestPerBucket($dated, $keep, $this->keepDaily, fn (CarbonImmutable $t) => $t->format('Y-m-d'),
            $now->startOfDay()->subDays(max($this->keepDaily - 1, 0)));
        $this->keepNewestPerBucket($dated, $keep, $this->keepWeekly, fn (CarbonImmutable $t) => $t->format('o-W'),
            $now->startOfWeek()->subWeeks(max($this->keepWeekly - 1, 0)));
        $this->keepNewestPerBucket($dated, $keep, $this->keepMonthly, fn (CarbonImmutable $t) => $t->format('Y-m'),
            $now->startOfMonth()->subMonths(max($this->keepMonthly - 1, 0)));

        // Jangan pernah menghapus cadangan terbaru, apa pun konfigurasinya.
        if ($dated !== []) {
            $keep[array_key_first($dated)] = true;
        }

        return array_values(array_filter(array_keys($dated), fn ($file) => !isset($keep[$file])));
    }

    /**
     * @param  array<string, CarbonImmutable>  $dated  Diurutkan terbaru lebih dulu.
     */
    protected function keepNewestPerBucket(array $dated, array &$keep, int $limit, callable $bucketOf, CarbonImmutable $since): void
    {
        if ($limit <= 0) {
            return;
        }

        $seen = [];
        foreach ($dated as $file => $ts) {
            if ($ts->lt($since)) {
                continue;
            }
            $bucket = $bucketOf($ts);
            if (!isset($seen[$bucket])) {
                $seen[$bucket] = true;
                $keep[$file] = true;
            }
        }
    }
}
