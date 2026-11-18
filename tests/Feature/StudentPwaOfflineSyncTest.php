<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Journal;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * PWA siswa: kontrak JSON untuk sinkronisasi jurnal offline, berkas PWA publik,
 * dan penanda PWA di layout siswa.
 */
class StudentPwaOfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $studentUser;
    protected StudentPkl $spkl;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        $school = School::create([
            'npsn' => '40404999', 'name' => 'SMK PWA Test', 'email' => 'pwa@test.sch.id',
            'address' => 'Bombana', 'phone' => '0401', 'is_active' => true,
        ]);
        $teacherUser = User::create([
            'school_id' => $school->id, 'name' => 'Guru PWA', 'email' => 'guru@pwa.sch.id',
            'password' => bcrypt('password'), 'role' => 'guru-pembimbing', 'is_active' => true,
        ]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'school_id' => $school->id, 'nip' => '1990']);
        $company = Company::create([
            'school_id' => $school->id, 'name' => 'PT Tambang Uji', 'address' => 'Bombana',
            'contact_person' => 'PIC', 'phone' => '0811', 'work_days' => '5_days',
            'latitude' => -4.6, 'longitude' => 121.9, 'radius_meters' => 100,
            'is_pkl_started' => true,
        ]);
        $major = Major::create(['school_id' => $school->id, 'name' => 'Geologi Pertambangan', 'code' => 'GP']);
        $class = SchoolClass::create([
            'school_id' => $school->id, 'major_id' => $major->id, 'name' => 'XII GP 1',
            'grade_level' => 'XII', 'academic_year' => '2026/2027',
        ]);
        $this->studentUser = User::create([
            'school_id' => $school->id, 'name' => 'Siswa PWA', 'email' => 'siswa@pwa.sch.id',
            'password' => bcrypt('password'), 'role' => 'siswa', 'is_active' => true,
        ]);
        $student = Student::create([
            'user_id' => $this->studentUser->id, 'class_id' => $class->id, 'nisn' => '0059999999', 'nis' => '9999',
        ]);
        $period = PklPeriod::create([
            'school_id' => $school->id, 'name' => 'Periode Uji',
            'start_date' => Carbon::today()->subMonths(2)->toDateString(),
            'end_date' => Carbon::today()->addMonths(2)->toDateString(),
            'is_active' => true,
        ]);
        $this->spkl = StudentPkl::create([
            'school_id' => $school->id, 'student_id' => $student->id, 'company_id' => $company->id,
            'pkl_period_id' => $period->id, 'teacher_id' => $teacher->id, 'status' => 'active',
        ]);
    }

    private function validDescription(): string
    {
        return 'Mengambil sampel batuan di area tambang dan mencatat koordinat titik bor bersama mentor lapangan.';
    }

    public function test_offline_sync_receives_json_on_success_with_original_date(): void
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $response = $this->actingAs($this->studentUser)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('student.journal.store'), [
                'date' => $yesterday,
                'activity_description' => $this->validDescription(),
                'action_type' => 'submit',
                'photos' => [UploadedFile::fake()->image('bukti.jpg', 400, 300)],
            ]);

        $response->assertOk()->assertJson(['status' => 'submitted', 'date' => $yesterday]);

        $journal = Journal::find($response->json('journal_id'));
        $this->assertSame($yesterday, $journal->date->toDateString(), 'Jurnal offline harus memakai tanggal saat ditulis, bukan saat terkirim.');
        $this->assertCount(1, $journal->photos);
    }

    public function test_offline_sync_receives_422_json_on_validation_error(): void
    {
        $this->actingAs($this->studentUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('student.journal.store'), [
                'date' => Carbon::today()->toDateString(),
                'activity_description' => 'terlalu pendek',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['activity_description']]);
    }

    public function test_offline_sync_receives_422_json_when_journal_already_approved(): void
    {
        Journal::create([
            'school_id' => $this->spkl->school_id, 'student_pkl_id' => $this->spkl->id,
            'date' => Carbon::today()->toDateString(), 'activity_description' => $this->validDescription(),
            'status' => 'approved',
        ]);

        $this->actingAs($this->studentUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('student.journal.store'), [
                'date' => Carbon::today()->toDateString(),
                'activity_description' => $this->validDescription(),
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'disetujui'));
    }

    public function test_regular_form_submission_still_redirects(): void
    {
        $this->actingAs($this->studentUser)
            ->post(route('student.journal.store'), [
                'date' => Carbon::today()->toDateString(),
                'activity_description' => $this->validDescription(),
            ])
            ->assertRedirect(route('student.journals'));
    }

    public function test_unauthenticated_sync_gets_401_json_not_login_page(): void
    {
        $this->withHeaders(['Accept' => 'application/json'])
            ->post(route('student.journal.store'), [
                'date' => Carbon::today()->toDateString(),
                'activity_description' => $this->validDescription(),
            ])
            ->assertStatus(401);
    }

    public function test_pwa_public_files_exist_and_manifest_is_valid(): void
    {
        foreach (['sw.js', 'offline.html', 'manifest.webmanifest', 'icons/icon-192.png', 'icons/icon-512.png', 'icons/icon-maskable-512.png', 'icons/apple-touch-icon.png'] as $file) {
            $this->assertFileExists(public_path($file));
        }

        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertStringStartsWith('/siswa/beranda', $manifest['start_url']);
        $sizes = array_column($manifest['icons'], 'sizes');
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
        $this->assertContains('maskable', array_column($manifest['icons'], 'purpose'));
    }

    public function test_student_layout_exposes_pwa_hooks(): void
    {
        $html = $this->actingAs($this->studentUser)->get(route('student.journals'))->assertOk()->getContent();

        $this->assertStringContainsString('rel="manifest"', $html);
        $this->assertStringContainsString('data-app-logo=', $html);
        $this->assertStringContainsString('data-user-id="' . $this->studentUser->id . '"', $html);
        $this->assertStringContainsString('data-journal-store-url=', $html);
        $this->assertStringContainsString('id="offline-journal-queue"', $html);
    }

    public function test_pwa_manifest_route_and_branding_sync_with_superadmin_logo(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        // 1. Default logo
        $response = $this->get('/manifest.webmanifest')->assertOk();
        $this->assertStringContainsString('application/manifest+json', $response->headers->get('Content-Type'));
        $data = $response->json();
        $this->assertNotEmpty($data['icons']);

        // 2. Custom logo upload by superadmin
        $fakeFile = \Illuminate\Http\UploadedFile::fake()->image('custom_logo.png', 200, 200);
        $path = $fakeFile->storeAs('branding', 'custom_logo.png', 'public');
        \App\Models\PlatformSetting::set('system_logo', $path);

        $this->artisan('vokatrack:sync-pwa')->assertSuccessful();

        $responseCustom = $this->get('/manifest.webmanifest')->assertOk();
        $customData = $responseCustom->json();
        $iconSources = array_column($customData['icons'], 'src');
        $this->assertStringContainsString('custom_logo.png', $iconSources[0]);

        // 3. Reset logo
        \App\Models\PlatformSetting::forget('system_logo');
        $this->artisan('vokatrack:sync-pwa')->assertSuccessful();
    }
}
