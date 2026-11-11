<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Major;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_announcement_with_image_and_view_it(): void
    {
        Storage::fake('public');

        $admin = User::where('role', 'admin-sekolah')->first();
        $major = Major::where('school_id', $admin->school_id)->first();

        $image = UploadedFile::fake()->image('banner_pembekalan.jpg', 800, 400);

        $response = $this->actingAs($admin)->post(route('adminsekolah.announcements.store'), [
            'title' => 'Pembekalan PKL Gelombang Khusus',
            'category' => 'penting',
            'target_role' => 'all',
            'target_major_id' => $major->id,
            'content' => 'Seluruh siswa dan guru wajib hadir di aula sekolah pukul 08.00 WIB.',
            'image' => $image,
            'is_pinned' => 1,
        ]);

        $response->assertRedirect(route('adminsekolah.announcements'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('announcements', [
            'school_id' => $admin->school_id,
            'author_id' => $admin->id,
            'title' => 'Pembekalan PKL Gelombang Khusus',
            'category' => 'penting',
            'target_role' => 'all',
            'target_major_id' => $major->id,
            'is_pinned' => true,
        ]);

        $announcement = Announcement::where('title', 'Pembekalan PKL Gelombang Khusus')->first();
        $this->assertNotNull($announcement->image_path);
        Storage::disk('public')->assertExists($announcement->image_path);
    }

    public function test_guru_can_create_announcement_for_students(): void
    {
        $guru = User::where('role', 'guru-pembimbing')->first();

        $response = $this->actingAs($guru)->post(route('guru.announcements.store'), [
            'title' => 'Arahan Pengisian Jurnal Mandiri',
            'category' => 'kegiatan',
            'target_role' => 'siswa',
            'content' => 'Harap mengunggah foto dokumentasi kegiatan minimal 1 lembar pada setiap logbook.',
            'is_pinned' => 0,
        ]);

        $response->assertRedirect(route('guru.announcements'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('announcements', [
            'school_id' => $guru->school_id,
            'author_id' => $guru->id,
            'title' => 'Arahan Pengisian Jurnal Mandiri',
            'target_role' => 'siswa',
        ]);
    }

    public function test_guru_cannot_edit_or_delete_another_users_announcement(): void
    {
        $admin = User::where('role', 'admin-sekolah')->first();
        $guru = User::where('role', 'guru-pembimbing')->first();

        $announcement = Announcement::create([
            'school_id' => $admin->school_id,
            'author_id' => $admin->id,
            'title' => 'Pengumuman Resmi Kepala Sekolah',
            'content' => 'Informasi penting dari kepala sekolah.',
            'category' => 'umum',
            'target_role' => 'all',
            'is_active' => true,
        ]);

        // Guru attempts to edit admin's announcement
        $editResponse = $this->actingAs($guru)->put(route('guru.announcements.update', $announcement), [
            'title' => 'Diubah oleh guru',
            'category' => 'umum',
            'target_role' => 'all',
            'content' => 'Konten diubah',
        ]);
        $editResponse->assertStatus(403);

        // Guru attempts to delete admin's announcement
        $deleteResponse = $this->actingAs($guru)->delete(route('guru.announcements.destroy', $announcement));
        $deleteResponse->assertStatus(403);
    }

    public function test_student_only_sees_announcements_matching_their_major_and_role(): void
    {
        $studentUser = User::where('role', 'siswa')->first();
        $studentMajorId = $studentUser->student->class->major_id;

        $otherMajor = Major::where('school_id', $studentUser->school_id)
            ->where('id', '!=', $studentMajorId)
            ->first();

        $admin = User::where('role', 'admin-sekolah')->where('school_id', $studentUser->school_id)->first();

        // 1. Target ALL & All Majors (Must be visible)
        $annAll = Announcement::create([
            'school_id' => $studentUser->school_id,
            'author_id' => $admin->id,
            'title' => 'TEST_ANN_ALL_CIVITAS',
            'content' => 'Untuk seluruh pengguna',
            'category' => 'umum',
            'target_role' => 'all',
            'target_major_id' => null,
            'is_active' => true,
        ]);

        // 2. Target SISWA & Student's Major (Must be visible)
        $annMyMajor = Announcement::create([
            'school_id' => $studentUser->school_id,
            'author_id' => $admin->id,
            'title' => 'TEST_ANN_MY_MAJOR',
            'content' => 'Khusus jurusan saya',
            'category' => 'kegiatan',
            'target_role' => 'siswa',
            'target_major_id' => $studentMajorId,
            'is_active' => true,
        ]);

        // 3. Target SISWA but DIFFERENT Major (MUST NOT BE VISIBLE!)
        $annOtherMajor = Announcement::create([
            'school_id' => $studentUser->school_id,
            'author_id' => $admin->id,
            'title' => 'TEST_ANN_OTHER_MAJOR_SECRET',
            'content' => 'Khusus jurusan lain, tidak boleh dilihat siswa ini',
            'category' => 'kegiatan',
            'target_role' => 'siswa',
            'target_major_id' => $otherMajor ? $otherMajor->id : 99999,
            'is_active' => true,
        ]);

        // 4. Target GURU ONLY (MUST NOT BE VISIBLE TO STUDENT)
        $annGuruOnly = Announcement::create([
            'school_id' => $studentUser->school_id,
            'author_id' => $admin->id,
            'title' => 'TEST_ANN_GURU_ONLY_SECRET',
            'content' => 'Khusus guru pembimbing',
            'category' => 'akademik',
            'target_role' => 'guru-pembimbing',
            'target_major_id' => null,
            'is_active' => true,
        ]);

        // Test in Student Mobile Beranda
        $homeResponse = $this->actingAs($studentUser)->get(route('student.home'));
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('TEST_ANN_ALL_CIVITAS');
        $homeResponse->assertSee('TEST_ANN_MY_MAJOR');
        $homeResponse->assertDontSee('TEST_ANN_OTHER_MAJOR_SECRET');
        $homeResponse->assertDontSee('TEST_ANN_GURU_ONLY_SECRET');

        // Test in Student Announcements Dedicated Page
        $listResponse = $this->actingAs($studentUser)->get(route('student.announcements'));
        $listResponse->assertStatus(200);
        $listResponse->assertSee('TEST_ANN_ALL_CIVITAS');
        $listResponse->assertSee('TEST_ANN_MY_MAJOR');
        $listResponse->assertDontSee('TEST_ANN_OTHER_MAJOR_SECRET');
        $listResponse->assertDontSee('TEST_ANN_GURU_ONLY_SECRET');
    }

    public function test_inactive_announcements_are_hidden_from_students(): void
    {
        $studentUser = User::where('role', 'siswa')->first();
        $admin = User::where('role', 'admin-sekolah')->where('school_id', $studentUser->school_id)->first();

        Announcement::create([
            'school_id' => $studentUser->school_id,
            'author_id' => $admin->id,
            'title' => 'TEST_INACTIVE_ANNOUNCEMENT_DRAFT',
            'content' => 'Draf pengumuman internal',
            'category' => 'umum',
            'target_role' => 'siswa',
            'is_active' => false,
        ]);

        $response = $this->actingAs($studentUser)->get(route('student.announcements'));
        $response->assertStatus(200);
        $response->assertDontSee('TEST_INACTIVE_ANNOUNCEMENT_DRAFT');
    }

    public function test_multi_tenant_isolation_prevents_viewing_other_schools_announcements(): void
    {
        // Create second school
        $schoolB = School::create([
            'npsn' => '99887766',
            'name' => 'SMK Negeri 99 Bandung',
            'address' => 'Bandung',
            'phone' => '022-998877',
            'email' => 'smk99@test.com',
            'is_active' => true,
        ]);

        $adminB = User::create([
            'school_id' => $schoolB->id,
            'name' => 'Admin Sekolah B',
            'email' => 'admin@smk99.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);

        $annSchoolB = Announcement::create([
            'school_id' => $schoolB->id,
            'author_id' => $adminB->id,
            'title' => 'PENGUMUMAN_RAHASIA_SEKOLAH_B',
            'content' => 'Hanya untuk sekolah B',
            'category' => 'umum',
            'target_role' => 'all',
            'is_active' => true,
        ]);

        $adminA = User::where('role', 'admin-sekolah')->where('school_id', '!=', $schoolB->id)->first();
        $studentA = User::where('role', 'siswa')->where('school_id', '!=', $schoolB->id)->first();

        // Admin A should not see School B's announcement
        $adminResponse = $this->actingAs($adminA)->get(route('adminsekolah.announcements'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertDontSee('PENGUMUMAN_RAHASIA_SEKOLAH_B');

        // Student A should not see School B's announcement
        $studentResponse = $this->actingAs($studentA)->get(route('student.announcements'));
        $studentResponse->assertStatus(200);
        $studentResponse->assertDontSee('PENGUMUMAN_RAHASIA_SEKOLAH_B');
    }

    public function test_announcement_detail_returns_json_for_ajax_modal(): void
    {
        $studentUser = User::where('role', 'siswa')->first();
        $admin = User::where('role', 'admin-sekolah')->where('school_id', $studentUser->school_id)->first();

        $ann = Announcement::create([
            'school_id' => $studentUser->school_id,
            'author_id' => $admin->id,
            'title' => 'Pengumuman Modal Test',
            'content' => 'Isi teks pengumuman untuk modal popup.',
            'category' => 'penting',
            'target_role' => 'all',
            'is_pinned' => true,
            'published_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($studentUser)->getJson(route('student.announcements.show', $ann));
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $ann->id,
            'title' => 'Pengumuman Modal Test',
            'category' => 'penting',
            'category_label' => 'Penting & Mendesak',
        ]);

        // Test latest announcement check endpoint for real-time auto popup
        $latestResponse = $this->actingAs($studentUser)->getJson(route('student.announcements.latest'));
        $latestResponse->assertStatus(200);
        $latestResponse->assertJson([
            'has_announcement' => true,
            'id' => $ann->id,
            'title' => 'Pengumuman Modal Test',
        ]);
    }
}
