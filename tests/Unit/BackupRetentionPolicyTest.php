<?php

namespace Tests\Unit;

use App\Services\BackupRetentionPolicy;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class BackupRetentionPolicyTest extends TestCase
{
    private function backupName(CarbonImmutable $t, string $suffix = 'abc123'): string
    {
        return 'auto_backup_vokatrack_' . $t->format('Ymd_His') . "_{$suffix}.vtbak";
    }

    public function test_parses_timestamp_from_filename(): void
    {
        $ts = BackupRetentionPolicy::timestampFromFilename('vokatrack/database/auto_backup_vokatrack_20261116_020000_x1y2z3.vtbak');
        $this->assertSame('2026-11-16 02:00:00', $ts->format('Y-m-d H:i:s'));
        $this->assertNull(BackupRetentionPolicy::timestampFromFilename('random-file.txt'));
    }

    public function test_keeps_last_seven_daily_backups(): void
    {
        $now = CarbonImmutable::parse('2026-11-16 03:00:00');
        $files = [];
        for ($i = 0; $i < 10; $i++) {
            $files[] = $this->backupName($now->subDays($i)->setTime(2, 0));
        }

        $delete = (new BackupRetentionPolicy(7, 0, 0))->filesToDelete($files, $now);

        $this->assertCount(3, $delete);
        $this->assertSame([$files[7], $files[8], $files[9]], $delete);
    }

    public function test_keeps_only_newest_backup_per_day(): void
    {
        $now = CarbonImmutable::parse('2026-11-16 23:00:00');
        $morning = $this->backupName($now->setTime(2, 0), 'aaaaaa');
        $evening = $this->backupName($now->setTime(20, 0), 'bbbbbb');

        $delete = (new BackupRetentionPolicy(7, 0, 0))->filesToDelete([$morning, $evening], $now);

        $this->assertSame([$morning], $delete);
    }

    public function test_weekly_and_monthly_buckets_extend_history(): void
    {
        $now = CarbonImmutable::parse('2026-11-16 03:00:00');
        $files = [];
        for ($i = 0; $i < 100; $i++) {
            $files[] = $this->backupName($now->subDays($i)->setTime(2, 0));
        }

        $policy = new BackupRetentionPolicy(7, 4, 3);
        $delete = $policy->filesToDelete($files, $now);
        $kept = array_values(array_diff($files, $delete));

        // Semua 7 hari terakhir dipertahankan.
        foreach (array_slice($files, 0, 7) as $f) {
            $this->assertContains($f, $kept);
        }
        // Ada cadangan bulan lalu dan 2 bulan lalu yang dipertahankan.
        $months = array_unique(array_map(fn ($f) => BackupRetentionPolicy::timestampFromFilename($f)->format('Y-m'), $kept));
        $this->assertContains('2026-10', $months);
        $this->assertContains('2026-09', $months);
        // Tidak ada yang lebih tua dari 3 bulan kalender.
        $this->assertNotContains('2026-08', $months);
    }

    public function test_never_deletes_the_newest_backup_or_unrecognised_files(): void
    {
        $now = CarbonImmutable::parse('2026-11-16 03:00:00');
        $ancient = $this->backupName($now->subYears(2));

        $delete = (new BackupRetentionPolicy(0, 0, 0))->filesToDelete([$ancient, 'notes.txt'], $now);

        $this->assertSame([], $delete);
    }
}
