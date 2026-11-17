<?php

namespace Tests\Feature;

use App\Console\Commands\RunScheduledBackup;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use App\Services\BackupService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Sprint 1 kesiapan produksi: backup terjadwal + off-site + retensi, peringatan
 * operasional ke super admin, dan pendaftaran jadwal cron.
 */
class ScheduledBackupAndMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected string $backupDir;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupDir = storage_path('framework/testing/backups_' . uniqid());
        File::ensureDirectoryExists($this->backupDir);

        // Arahkan BackupService ke direktori sementara agar tidak menyentuh cadangan asli.
        $dir = $this->backupDir;
        $this->app->instance(BackupService::class, new class($dir) extends BackupService {
            public function __construct(string $dir)
            {
                $this->backupDir = $dir;
            }
        });

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'sa@vokatrack.id',
            'password' => 'secret-super-1',
            'role' => 'super-admin',
            'is_active' => true,
        ]);

        config([
            'backup.offsite_disk' => 'backup_offsite_test',
            'backup.offsite_path' => 'vokatrack/database',
            'backup.local_keep_days' => 7,
            'backup.retention' => ['daily' => 7, 'weekly' => 4, 'monthly' => 3],
        ]);
        Storage::fake('backup_offsite_test');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupDir);
        parent::tearDown();
    }

    public function test_backup_command_creates_encrypted_snapshot_and_uploads_offsite(): void
    {
        $this->artisan('vokatrack:backup')->assertSuccessful();

        $local = File::files($this->backupDir);
        $this->assertCount(1, $local);
        $filename = $local[0]->getFilename();
        $this->assertStringStartsWith('auto_backup_vokatrack_', $filename);

        $remote = "vokatrack/database/{$filename}";
        Storage::disk('backup_offsite_test')->assertExists($remote);
        $this->assertSame($local[0]->getSize(), Storage::disk('backup_offsite_test')->size($remote));

        // Isi off-site tetap terenkripsi: tidak ada email super admin dalam bentuk polos.
        $this->assertStringNotContainsString('sa@vokatrack.id', Storage::disk('backup_offsite_test')->get($remote));

        $status = Cache::get(RunScheduledBackup::STATUS_CACHE_KEY);
        $this->assertSame('success', $status['status']);
        $this->assertTrue($status['offsite']);
        $this->assertTrue(ActivityLog::where('action', 'backup_scheduled')->exists());
    }

    public function test_backup_without_offsite_disk_still_succeeds_locally(): void
    {
        config(['backup.offsite_disk' => null]);

        $this->artisan('vokatrack:backup')
            ->expectsOutputToContain('HANYA tersimpan di server ini')
            ->assertSuccessful();

        $this->assertCount(1, File::files($this->backupDir));
        $this->assertFalse(Cache::get(RunScheduledBackup::STATUS_CACHE_KEY)['offsite']);
    }

    public function test_local_retention_removes_only_old_automatic_backups(): void
    {
        $old = 'auto_backup_vokatrack_' . now()->subDays(10)->format('Ymd_His') . '_abc123.vtbak';
        $recent = 'auto_backup_vokatrack_' . now()->subDays(2)->format('Ymd_His') . '_def456.vtbak';
        $manual = 'backup_vokatrack_' . now()->subDays(30)->format('Ymd_His') . '_ghi789.vtbak';
        $safety = 'pre_restore_safety_' . now()->subDays(30)->format('Ymd_His') . '_jkl012.vtbak';
        $oldDeploy = 'pre_deploy_vokatrack_' . now()->subDays(10)->format('Ymd_His') . '_mno345.vtbak';
        foreach ([$old, $recent, $manual, $safety, $oldDeploy] as $f) {
            File::put("{$this->backupDir}/{$f}", 'x');
        }

        $this->artisan('vokatrack:backup', ['--no-upload' => true])->assertSuccessful();

        $this->assertFileDoesNotExist("{$this->backupDir}/{$old}");
        $this->assertFileDoesNotExist("{$this->backupDir}/{$oldDeploy}");
        $this->assertFileExists("{$this->backupDir}/{$recent}");
        $this->assertFileExists("{$this->backupDir}/{$manual}", 'Cadangan manual tidak boleh dihapus otomatis.');
        $this->assertFileExists("{$this->backupDir}/{$safety}", 'Snapshot pra-restore tidak boleh dihapus otomatis.');
    }

    public function test_offsite_retention_applies_gfs_policy(): void
    {
        $disk = Storage::disk('backup_offsite_test');
        $names = [];
        // 120 hari cadangan harian di off-site.
        for ($i = 1; $i <= 120; $i++) {
            $name = 'auto_backup_vokatrack_' . now()->subDays($i)->format('Ymd') . '_020000_r' . $i . '.vtbak';
            $disk->put("vokatrack/database/{$name}", 'x');
            $names[] = $name;
        }
        $disk->put('vokatrack/database/backup_vokatrack_20200101_000000_manual.vtbak', 'x');

        $this->artisan('vokatrack:backup')->assertSuccessful();

        $remaining = $disk->files('vokatrack/database');
        $this->assertContains('vokatrack/database/backup_vokatrack_20200101_000000_manual.vtbak', $remaining);

        $auto = array_filter($remaining, fn ($f) => str_contains($f, 'auto_backup_vokatrack_'));
        // 7 harian + maks 4 mingguan + maks 3 bulanan (sebagian tumpang tindih) — jauh di bawah 121.
        $this->assertLessThanOrEqual(14, count($auto));
        $this->assertGreaterThanOrEqual(7, count($auto));
        // Cadangan 100 hari lalu pasti sudah dihapus.
        $this->assertNotContains("vokatrack/database/{$names[99]}", $remaining);
    }

    public function test_backup_failure_alerts_super_admins_and_returns_failure(): void
    {
        Notification::fake();

        $this->app->instance(BackupService::class, new class extends BackupService {
            public function __construct() {}

            public function createDatabaseBackup(string $prefix = 'backup_vokatrack'): array
            {
                throw new \RuntimeException('Disk penuh');
            }
        });

        $this->artisan('vokatrack:backup')->assertFailed();

        Notification::assertSentTo($this->superAdmin, SystemAlertNotification::class, function ($n) {
            return $n->event === 'backup_failed' && str_contains($n->message, 'Disk penuh');
        });
        $this->assertSame('failed', Cache::get(RunScheduledBackup::STATUS_CACHE_KEY)['status']);
        $this->assertTrue(ActivityLog::where('action', 'backup_scheduled_failed')->exists());
    }

    public function test_alert_mail_channel_only_used_when_smtp_configured(): void
    {
        $n = new SystemAlertNotification('x', 'T', 'M', 'http://localhost');

        config(['mail.default' => 'log']);
        $this->assertSame(['database'], $n->via($this->superAdmin));

        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.example.com', 'mail.mailers.smtp.username' => 'user']);
        $this->assertSame(['database', 'mail'], $n->via($this->superAdmin));
    }

    public function test_failed_jobs_check_alerts_only_for_recent_failures(): void
    {
        Notification::fake();

        $this->artisan('vokatrack:check-failed-jobs')->assertSuccessful();
        Notification::assertNothingSent();

        DB::table('failed_jobs')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database', 'queue' => 'default',
            'payload' => '{}', 'exception' => 'Boom',
            'failed_at' => now()->subMinutes(5),
        ]);

        $this->artisan('vokatrack:check-failed-jobs')->assertSuccessful();
        Notification::assertSentTo($this->superAdmin, SystemAlertNotification::class, fn ($n) => $n->event === 'failed_jobs');
    }

    public function test_scheduler_registers_backup_and_monitoring_jobs(): void
    {
        $commands = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command)->implode("\n");

        $this->assertStringContainsString('vokatrack:evaluate-absence', $commands);
        $this->assertStringContainsString('saas:evaluate-subscriptions', $commands);
        $this->assertStringContainsString('vokatrack:backup', $commands);
        $this->assertStringContainsString('vokatrack:check-failed-jobs', $commands);
    }

    public function test_web_requests_work_with_monitoring_context_middleware(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.backups'))
            ->assertOk();
    }
}
