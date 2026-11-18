<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\IncidentReport;
use App\Models\Journal;
use App\Models\JournalPhoto;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSubscription;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\SubscriptionPlan;
use App\Models\Supervisor;
use App\Models\Teacher;
use App\Models\User;
use App\Services\BackupService;
use App\Services\SecureFileService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class P1DataSafetyAndPrivacyTest extends TestCase
{
    use RefreshDatabase;

    /** PNG 1x1 piksel yang valid. */
    private const TINY_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    protected School $school;
    protected SchoolClass $class;
    protected Company $company;
    protected Company $otherCompany;
    protected User $studentUser;
    protected Student $student;
    protected User $otherStudentUser;
    protected Student $otherStudent;
    protected User $teacherUser;
    protected User $dudiUser;
    protected User $otherDudiUser;
    protected StudentPkl $spkl;
    protected StudentPkl $otherSpkl;

    protected array $createdBackups = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(SecureFileService::DISK);
        Storage::fake(SecureFileService::LEGACY_DISK);

        $this->school = School::create([
            'name' => 'SMK Negeri Uji',
            'npsn' => '20999001',
            'email' => 'smk-uji@example.com',
            'address' => 'Jl. Uji No. 1',
            'phone' => '0262-000001',
            'is_active' => true,
        ]);

        $major = Major::create(['school_id' => $this->school->id, 'name' => 'Teknik Komputer', 'code' => 'TKJ']);
        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'major_id' => $major->id,
            'name' => 'XII TKJ 1',
            'academic_year' => '2026/2027',
        ]);

        $this->company = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Mitra Satu',
            'address' => 'Jl. Industri 1',
            'contact_person' => 'PIC 1',
            'phone' => '0262-101',
            'latitude' => -7.2,
            'longitude' => 107.9,
            'radius_meters' => 100,
            'is_pkl_started' => true,
        ]);
        $this->otherCompany = Company::create([
            'school_id' => $this->school->id,
            'name' => 'PT Mitra Dua',
            'address' => 'Jl. Industri 2',
            'contact_person' => 'PIC 2',
            'phone' => '0262-102',
            'latitude' => -7.3,
            'longitude' => 107.8,
            'radius_meters' => 100,
            'is_pkl_started' => true,
        ]);

        $this->teacherUser = $this->makeUser('guru-pembimbing', 'guru@example.com');
        $teacher = Teacher::create(['user_id' => $this->teacherUser->id, 'nip' => '198001012005011001']);
        $otherTeacher = Teacher::create(['user_id' => $this->makeUser('guru-pembimbing', 'guru2@example.com')->id, 'nip' => '198001012005011002']);

        $this->dudiUser = $this->makeUser('pembimbing-dudi', 'dudi1@example.com');
        Supervisor::create(['user_id' => $this->dudiUser->id, 'company_id' => $this->company->id, 'position' => 'HRD']);
        $this->otherDudiUser = $this->makeUser('pembimbing-dudi', 'dudi2@example.com');
        Supervisor::create(['user_id' => $this->otherDudiUser->id, 'company_id' => $this->otherCompany->id, 'position' => 'HRD']);

        $period = PklPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Periode Uji',
            'start_date' => Carbon::today()->subMonth()->toDateString(),
            'end_date' => Carbon::today()->addMonths(2)->toDateString(),
            'is_active' => true,
        ]);

        $this->studentUser = $this->makeUser('siswa', 'siswa1@example.com', 'Siswa Rahasia Satu');
        $this->student = Student::create(['user_id' => $this->studentUser->id, 'class_id' => $this->class->id, 'nis' => '1001', 'nisn' => '0099000001']);
        $this->otherStudentUser = $this->makeUser('siswa', 'siswa2@example.com');
        $this->otherStudent = Student::create(['user_id' => $this->otherStudentUser->id, 'class_id' => $this->class->id, 'nis' => '1002', 'nisn' => '0099000002']);

        $this->spkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'company_id' => $this->company->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);
        $this->otherSpkl = StudentPkl::create([
            'school_id' => $this->school->id,
            'student_id' => $this->otherStudent->id,
            'company_id' => $this->otherCompany->id,
            'pkl_period_id' => $period->id,
            'teacher_id' => $otherTeacher->id,
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        $service = app(BackupService::class);
        foreach ($this->createdBackups as $filename) {
            $service->deleteBackup($filename);
        }
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeUser(string $role, string $email, ?string $name = null): User
    {
        return User::create([
            'school_id' => $this->school->id,
            'name' => $name ?? ucfirst($role) . ' ' . $email,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    // ------------------------------------------------------------------
    // 1. Backup & restore
    // ------------------------------------------------------------------

    public function test_every_migrated_table_is_either_backed_up_or_explicitly_excluded(): void
    {
        $tables = collect(Schema::getTables())->pluck('name')
            ->reject(fn ($name) => str_starts_with($name, 'sqlite_'))
            ->values()->all();

        $unclassified = array_diff($tables, BackupService::BACKUP_TABLES, BackupService::EXCLUDED_TABLES);

        $this->assertSame([], array_values($unclassified), 'Tabel baru harus dimasukkan ke BACKUP_TABLES atau EXCLUDED_TABLES.');
        $this->assertContains('classes', BackupService::BACKUP_TABLES);
    }

    public function test_backup_is_encrypted_and_restore_recovers_all_domain_tables(): void
    {
        $journal = Journal::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => Carbon::today()->toDateString(),
            'activity_description' => 'Konfigurasi jaringan kantor',
            'status' => 'submitted',
        ]);
        $photo = JournalPhoto::create(['journal_id' => $journal->id, 'photo_path' => 'schools/1/journals/x.png']);

        $service = app(BackupService::class);
        $backup = $service->createDatabaseBackup();
        $this->createdBackups[] = $backup['filename'];

        $this->assertStringEndsWith('.vtbak', $backup['filename']);
        $this->assertSame(1, $backup['row_counts']['classes']);
        $this->assertSame(1, $backup['row_counts']['journal_photos']);

        $raw = File::get($backup['path']);
        $this->assertStringNotContainsString('Siswa Rahasia Satu', $raw, 'Isi backup harus terenkripsi.');
        $this->assertStringNotContainsString('$2y$', $raw, 'Hash kata sandi tidak boleh tersimpan polos.');

        // Rusak data setelah backup.
        DB::table('journal_photos')->delete();
        $this->school->update(['name' => 'NAMA RUSAK']);
        DB::table('classes')->where('id', $this->class->id)->update(['name' => 'KELAS RUSAK']);

        $result = $service->restoreDatabaseBackup($backup['filename']);
        $this->createdBackups[] = $result['safety_snapshot'];

        $this->assertSame('SMK Negeri Uji', School::find($this->school->id)->name);
        $this->assertSame('XII TKJ 1', DB::table('classes')->where('id', $this->class->id)->value('name'));
        $this->assertTrue(DB::table('journal_photos')->where('id', $photo->id)->exists());
        $this->assertFileExists(storage_path('app/backups/' . $result['safety_snapshot']));
    }

    public function test_restore_rejects_backup_encrypted_with_different_key_without_touching_data(): void
    {
        $service = app(BackupService::class);
        $backup = $service->createDatabaseBackup();
        $this->createdBackups[] = $backup['filename'];

        $lines = file($backup['path']);
        $header = json_decode($lines[0], true);
        $header['key_fingerprint'] = 'ffffffffffffffff';
        $lines[0] = json_encode($header) . "\n";
        file_put_contents($backup['path'], implode('', $lines));

        $this->school->update(['name' => 'DATA SAAT INI']);

        try {
            $service->restoreDatabaseBackup($backup['filename']);
            $this->fail('Restore seharusnya ditolak.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('APP_KEY', $e->getMessage());
        }

        $this->assertSame('DATA SAAT INI', $this->school->fresh()->name);
    }

    public function test_legacy_json_backup_maps_school_classes_to_classes(): void
    {
        $filename = 'backup_vokatrack_legacy_test.json';
        $this->createdBackups[] = $filename;

        // Snapshot lengkap gaya v1: tabel kelas tersimpan dengan nama keliru "school_classes".
        $tables = [];
        foreach (BackupService::BACKUP_TABLES as $table) {
            $rows = DB::table($table)->get()->map(fn ($r) => (array) $r)->all();
            if ($table === 'classes') {
                $rows = array_map(fn ($r) => array_merge($r, ['name' => 'KELAS DARI BACKUP LAMA']), $rows);
                $tables['school_classes'] = $rows;
            } else {
                $tables[$table] = $rows;
            }
        }

        File::put(storage_path('app/backups/' . $filename), json_encode([
            'app' => 'VokaTrack Cloud',
            'version' => '1.3.0',
            'tables' => $tables,
        ]));

        $result = app(BackupService::class)->restoreDatabaseBackup($filename);
        $this->createdBackups[] = $result['safety_snapshot'];

        $this->assertSame('KELAS DARI BACKUP LAMA', DB::table('classes')->where('id', $this->class->id)->value('name'));
    }

    // ------------------------------------------------------------------
    // 2. Check-out tidak menghapus bukti check-in
    // ------------------------------------------------------------------

    public function test_checkout_preserves_out_of_radius_reason_and_fake_gps_flag(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(17, 0));

        $attendance = Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => Carbon::today()->toDateString(),
            'status' => 'hadir',
            'checkin_time' => '07:30:00',
            'is_within_radius' => false,
            'is_mock_detected' => true,
            'checkout_notes' => 'Alasan Luar Radius: survey lokasi | [FLAG] Indikasi Fake GPS / Mock Location Terdeteksi',
        ]);

        $this->actingAs($this->studentUser)
            ->post(route('student.checkout'), ['checkout_notes' => 'Pulang tepat waktu'])
            ->assertSessionHasNoErrors();

        $notes = $attendance->fresh()->checkout_notes;
        $this->assertStringContainsString('[FLAG] Indikasi Fake GPS', $notes);
        $this->assertStringContainsString('Alasan Luar Radius: survey lokasi', $notes);
        $this->assertStringContainsString('Catatan Pulang: Pulang tepat waktu', $notes);
        $this->assertNotNull($attendance->fresh()->checkout_time);
    }

    // ------------------------------------------------------------------
    // 3. Berkas pribadi di disk privat + kontrol akses
    // ------------------------------------------------------------------

    public function test_checkin_selfie_is_stored_privately_and_served_only_to_authorized_users(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 15));

        $this->actingAs($this->studentUser)->postJson(route('student.attendance.checkin'), [
            'latitude' => -7.2,
            'longitude' => 107.9,
            'accuracy' => 10,
            'photo' => 'data:image/png;base64,' . self::TINY_PNG,
        ])->assertOk();

        $attendance = Attendance::withoutGlobalScopes()->where('student_pkl_id', $this->spkl->id)->firstOrFail();
        $path = $attendance->checkin_photo_path;

        Storage::disk(SecureFileService::DISK)->assertExists($path);
        Storage::disk(SecureFileService::LEGACY_DISK)->assertMissing($path);
        $this->assertStringEndsWith('.png', $path);

        $url = route('secure.file', ['type' => 'presensi', 'id' => $attendance->id]);
        $this->assertSame($url, $attendance->checkin_photo_url);

        auth()->logout();
        $this->get($url)->assertRedirect(route('login'));

        $this->actingAs($this->studentUser)->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($this->teacherUser)->get($url)->assertOk();
        $this->actingAs($this->dudiUser)->get($url)->assertOk();
        $this->actingAs($this->otherStudentUser)->get($url)->assertForbidden();
        $this->actingAs($this->otherDudiUser)->get($url)->assertForbidden();
    }

    public function test_checkin_rejects_payload_that_is_not_a_real_image(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 15));

        $this->actingAs($this->studentUser)->post(route('student.attendance.checkin'), [
            'latitude' => -7.2,
            'longitude' => 107.9,
            'photo' => 'data:image/jpeg;base64,' . base64_encode('<html><script>alert(1)</script></html>'),
        ])->assertSessionHasErrors(['photo']);

        $this->assertSame(0, Attendance::withoutGlobalScopes()->count());
        $this->assertSame([], Storage::disk(SecureFileService::DISK)->allFiles());
    }

    public function test_checkin_rejects_oversized_selfie_payload(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 15));

        $this->actingAs($this->studentUser)->post(route('student.attendance.checkin'), [
            'latitude' => -7.2,
            'longitude' => 107.9,
            'photo' => 'data:image/jpeg;base64,' . str_repeat('A', 1500 * 1024),
        ])->assertSessionHasErrors(['photo']);

        $this->assertSame(0, Attendance::withoutGlobalScopes()->count());
        $this->assertSame([], Storage::disk(SecureFileService::DISK)->allFiles());
    }

    public function test_checkin_rejects_selfie_with_excessive_dimensions(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 15));

        // Header PNG kecil yang mengklaim 4000x4000 piksel (hanya header IHDR yang dibaca server).
        $ihdr = pack('N', 4000) . pack('N', 4000) . "\x08\x02\x00\x00\x00";
        $png = "\x89PNG\r\n\x1a\n" . pack('N', 13) . 'IHDR' . $ihdr . pack('N', crc32('IHDR' . $ihdr));

        $this->actingAs($this->studentUser)->post(route('student.attendance.checkin'), [
            'latitude' => -7.2,
            'longitude' => 107.9,
            'photo' => 'data:image/png;base64,' . base64_encode($png),
        ])->assertSessionHasErrors(['photo']);

        $this->assertSame(0, Attendance::withoutGlobalScopes()->count());
        $this->assertSame([], Storage::disk(SecureFileService::DISK)->allFiles());
    }

    public function test_checkout_selfie_is_stored_privately_and_served_only_to_authorized_users(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(16, 30));

        $attendance = Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => Carbon::today()->toDateString(),
            'checkin_time' => '07:30:00',
            'status' => 'hadir',
            'is_within_radius' => true,
        ]);

        $this->actingAs($this->studentUser)->postJson(route('student.checkout'), [
            'latitude' => -7.2,
            'longitude' => 107.9,
            'accuracy' => 10,
            'photo' => 'data:image/png;base64,' . self::TINY_PNG,
            'checkout_notes' => 'Pulang kerja',
        ])->assertOk();

        $attendance->refresh();
        $path = $attendance->checkout_photo_path;

        $this->assertNotNull($path);
        Storage::disk(SecureFileService::DISK)->assertExists($path);
        Storage::disk(SecureFileService::LEGACY_DISK)->assertMissing($path);
        $this->assertStringEndsWith('.png', $path);

        $url = route('secure.file', ['type' => 'presensi-pulang', 'id' => $attendance->id]);
        $this->assertSame($url, $attendance->checkout_photo_url);

        auth()->logout();
        $this->get($url)->assertRedirect(route('login'));

        $this->actingAs($this->studentUser)->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($this->teacherUser)->get($url)->assertOk();
        $this->actingAs($this->dudiUser)->get($url)->assertOk();
        $this->actingAs($this->otherStudentUser)->get($url)->assertForbidden();
        $this->actingAs($this->otherDudiUser)->get($url)->assertForbidden();
    }

    public function test_checkout_rejects_payload_that_is_not_a_real_image(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(16, 30));

        $attendance = Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => Carbon::today()->toDateString(),
            'checkin_time' => '07:30:00',
            'status' => 'hadir',
            'is_within_radius' => true,
        ]);

        $this->actingAs($this->studentUser)->post(route('student.checkout'), [
            'latitude' => -7.2,
            'longitude' => 107.9,
            'photo' => 'data:image/jpeg;base64,' . base64_encode('<html><script>alert(1)</script></html>'),
        ])->assertSessionHasErrors(['photo']);

        $attendance->refresh();
        $this->assertNull($attendance->checkout_photo_path);
        $this->assertSame([], Storage::disk(SecureFileService::DISK)->allFiles());
    }

    public function test_checkout_rejects_oversized_selfie_payload(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(16, 30));

        $attendance = Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => Carbon::today()->toDateString(),
            'checkin_time' => '07:30:00',
            'status' => 'hadir',
            'is_within_radius' => true,
        ]);

        $this->actingAs($this->studentUser)->post(route('student.checkout'), [
            'latitude' => -7.2,
            'longitude' => 107.9,
            'photo' => 'data:image/jpeg;base64,' . str_repeat('A', 1500 * 1024),
        ])->assertSessionHasErrors(['photo']);

        $attendance->refresh();
        $this->assertNull($attendance->checkout_photo_path);
        $this->assertSame([], Storage::disk(SecureFileService::DISK)->allFiles());
    }

    public function test_checkout_rejects_selfie_with_excessive_dimensions(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(16, 30));

        $attendance = Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => Carbon::today()->toDateString(),
            'checkin_time' => '07:30:00',
            'status' => 'hadir',
            'is_within_radius' => true,
        ]);

        $ihdr = pack('N', 4000) . pack('N', 4000) . "\x08\x02\x00\x00\x00";
        $png = "\x89PNG\r\n\x1a\n" . pack('N', 13) . 'IHDR' . $ihdr . pack('N', crc32('IHDR' . $ihdr));

        $this->actingAs($this->studentUser)->post(route('student.checkout'), [
            'latitude' => -7.2,
            'longitude' => 107.9,
            'photo' => 'data:image/png;base64,' . base64_encode($png),
        ])->assertSessionHasErrors(['photo']);

        $attendance->refresh();
        $this->assertNull($attendance->checkout_photo_path);
        $this->assertSame([], Storage::disk(SecureFileService::DISK)->allFiles());
    }

    public function test_incident_attachment_is_hidden_from_dudi_and_other_tenants(): void
    {
        Storage::disk(SecureFileService::DISK)->put('schools/1/incidents/a.png', base64_decode(self::TINY_PNG));
        $report = IncidentReport::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'category' => 'administrative',
            'urgency' => 'medium',
            'subject' => 'Kendala',
            'description' => 'Deskripsi kendala',
            'attachment_path' => 'schools/1/incidents/a.png',
            'status' => 'submitted',
        ]);

        $otherSchool = School::create(['name' => 'SMK Lain', 'npsn' => '20999002', 'email' => 'lain@example.com', 'address' => 'Jl. Lain', 'phone' => '0262-2', 'is_active' => true]);
        $otherAdmin = User::create([
            'school_id' => $otherSchool->id,
            'name' => 'Admin Lain',
            'email' => 'admin-lain@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
        $admin = $this->makeUser('admin-sekolah', 'admin@example.com');

        $url = $report->attachment_url;

        $this->actingAs($this->teacherUser)->get($url)->assertOk();
        $this->actingAs($admin)->get($url)->assertOk();
        $this->actingAs($this->dudiUser)->get($url)->assertForbidden();
        $this->actingAs($otherAdmin)->get($url)->assertNotFound();
    }

    public function test_legacy_files_on_public_disk_are_still_served_and_can_be_migrated(): void
    {
        Storage::disk(SecureFileService::LEGACY_DISK)->put('schools/1/attendances/old.png', base64_decode(self::TINY_PNG));
        $attendance = Attendance::create([
            'school_id' => $this->school->id,
            'student_pkl_id' => $this->spkl->id,
            'date' => Carbon::today()->toDateString(),
            'status' => 'hadir',
            'checkin_photo_path' => 'schools/1/attendances/old.png',
        ]);

        $this->actingAs($this->teacherUser)->get($attendance->checkin_photo_url)->assertOk();

        $this->artisan('vokatrack:migrate-private-files')->assertSuccessful();

        Storage::disk(SecureFileService::DISK)->assertExists('schools/1/attendances/old.png');
        Storage::disk(SecureFileService::LEGACY_DISK)->assertMissing('schools/1/attendances/old.png');
        $this->actingAs($this->teacherUser)->get($attendance->checkin_photo_url)->assertOk();
    }

    // ------------------------------------------------------------------
    // 4. Portal orang tua mengikuti status sekolah & lisensi
    // ------------------------------------------------------------------

    public function test_parent_portal_is_blocked_when_school_is_deactivated(): void
    {
        $url = route('parent.monitor', $this->student->fresh()->parent_token);
        $this->get($url)->assertOk();

        $this->school->update(['is_active' => false]);

        $this->get($url)->assertForbidden()->assertSee('layanan sekolah sedang dinonaktifkan');
    }

    public function test_parent_portal_is_blocked_when_subscription_expired_beyond_grace_period(): void
    {
        $plan = SubscriptionPlan::create([
            'code' => 'uji',
            'name' => 'Paket Uji',
            'price' => 0,
            'billing_period' => 'monthly',
            'max_students' => 50,
            'max_storage_mb' => 1024,
            'features' => ['attendance' => true],
        ]);
        SchoolSubscription::create([
            'school_id' => $this->school->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'start_date' => Carbon::today()->subMonths(3),
            'end_date' => Carbon::today()->subDays(30),
            'status' => 'expired',
        ]);

        $this->get(route('parent.monitor', $this->student->fresh()->parent_token))
            ->assertForbidden()
            ->assertSee('masa layanan sekolah telah berakhir');
    }

    public function test_parent_photo_route_only_serves_photos_of_the_token_owner(): void
    {
        Storage::disk(SecureFileService::DISK)->put('schools/1/journals/own.png', base64_decode(self::TINY_PNG));
        Storage::disk(SecureFileService::DISK)->put('schools/1/journals/other.png', base64_decode(self::TINY_PNG));

        $ownJournal = Journal::create(['school_id' => $this->school->id, 'student_pkl_id' => $this->spkl->id, 'date' => Carbon::today()->toDateString(), 'activity_description' => 'A', 'status' => 'submitted']);
        $otherJournal = Journal::create(['school_id' => $this->school->id, 'student_pkl_id' => $this->otherSpkl->id, 'date' => Carbon::today()->toDateString(), 'activity_description' => 'B', 'status' => 'submitted']);
        $ownPhoto = JournalPhoto::create(['journal_id' => $ownJournal->id, 'photo_path' => 'schools/1/journals/own.png']);
        $otherPhoto = JournalPhoto::create(['journal_id' => $otherJournal->id, 'photo_path' => 'schools/1/journals/other.png']);

        $token = $this->student->fresh()->parent_token;

        $this->get($ownPhoto->parentUrl($token))->assertOk();
        $this->get($otherPhoto->parentUrl($token))->assertNotFound();

        $this->school->update(['is_active' => false]);
        $this->get($ownPhoto->parentUrl($token))->assertNotFound();
    }

    public function test_student_regenerating_parent_token_is_audit_logged(): void
    {
        $this->actingAs($this->studentUser)
            ->post(route('student.parent-token.regenerate'))
            ->assertRedirect(route('student.profile'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'parent_token_regenerated_by_student',
            'user_id' => $this->studentUser->id,
        ]);
    }
}
