<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Major;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAnnouncementAutoPopupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Announcement::query()->delete();
    }

    public function test_unread_announcements_are_passed_to_student_home(): void
    {
        $studentUser = User::where('role', 'siswa')->first();
        $this->assertNotNull($studentUser);
        $student = $studentUser->student;
        $studentMajorId = $student->class?->major_id;

        $admin = User::where('role', 'admin-sekolah')->where('school_id', $studentUser->school_id)->first();

        // Buat 2 pengumuman aktif untuk siswa
        $ann1 = Announcement::create([
            'school_id' => $studentUser->school_id,
            'author_id' => $admin->id,
            'title' => 'Pengumuman Penting 1',
            'content' => 'Isi pengumuman nomor 1',
            'category' => 'penting',
            'target_role' => 'siswa',
            'target_major_id' => $studentMajorId,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $ann2 = Announcement::create([
            'school_id' => $studentUser->school_id,
            'author_id' => $admin->id,
            'title' => 'Pengumuman Kegiatan 2',
            'content' => 'Isi pengumuman nomor 2',
            'category' => 'kegiatan',
            'target_role' => 'all',
            'target_major_id' => null,
            'is_active' => true,
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($studentUser)->get(route('student.home'));
        $response->assertStatus(200);
        $response->assertViewHas('unreadAnnouncements');

        $unread = $response->viewData('unreadAnnouncements');
        $this->assertCount(2, $unread);
        $unreadIds = collect($unread)->pluck('id')->toArray();
        $this->assertContains($ann1->id, $unreadIds);
        $this->assertContains($ann2->id, $unreadIds);
    }

    public function test_marking_announcement_as_read_persists_and_removes_from_unread_queue(): void
    {
        $studentUser = User::where('role', 'siswa')->first();
        $admin = User::where('role', 'admin-sekolah')->where('school_id', $studentUser->school_id)->first();

        $ann1 = Announcement::create([
            'school_id' => $studentUser->school_id,
            'author_id' => $admin->id,
            'title' => 'Pengumuman Antrean 1',
            'content' => 'Harus dibaca',
            'category' => 'penting',
            'target_role' => 'all',
            'target_major_id' => null,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $ann2 = Announcement::create([
            'school_id' => $studentUser->school_id,
            'author_id' => $admin->id,
            'title' => 'Pengumuman Antrean 2',
            'content' => 'Harus dibaca juga setelah nomor 1 di close',
            'category' => 'umum',
            'target_role' => 'all',
            'target_major_id' => null,
            'is_active' => true,
            'published_at' => now()->subMinute(),
        ]);

        // Simulasikan siswa menutup pengumuman 1 (menandai sebagai read)
        $readResponse = $this->actingAs($studentUser)->postJson(route('student.announcements.read', $ann1->id));
        $readResponse->assertStatus(200);
        $readResponse->assertJson(['success' => true]);

        // Pastikan tersimpan di tabel announcement_reads
        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $ann1->id,
            'user_id' => $studentUser->id,
        ]);

        // Kunjungan berikutnya ke beranda hanya menyisakan pengumuman 2 di unreadAnnouncements
        $homeResponse = $this->actingAs($studentUser)->get(route('student.home'));
        $homeResponse->assertStatus(200);

        $unread = $homeResponse->viewData('unreadAnnouncements');
        $this->assertCount(1, $unread);
        $this->assertEquals($ann2->id, $unread[0]['id']);

        // Siswa menutup pengumuman 2
        $this->actingAs($studentUser)->postJson(route('student.announcements.read', $ann2->id))
            ->assertStatus(200);

        // Sekarang antrean unread sudah kosong total
        $homeResponse2 = $this->actingAs($studentUser)->get(route('student.home'));
        $unreadAfterAll = $homeResponse2->viewData('unreadAnnouncements');
        $this->assertCount(0, $unreadAfterAll);
    }

    public function test_read_announcements_can_be_reopened_on_dedicated_page(): void
    {
        $studentUser = User::where('role', 'siswa')->first();
        $admin = User::where('role', 'admin-sekolah')->where('school_id', $studentUser->school_id)->first();

        $ann = Announcement::create([
            'school_id' => $studentUser->school_id,
            'author_id' => $admin->id,
            'title' => 'Pengumuman Bisa Dibuka Ulang',
            'content' => 'Dapat dibuka kembali di halaman pengumuman kapan saja',
            'category' => 'umum',
            'target_role' => 'siswa',
            'target_major_id' => null,
            'is_active' => true,
            'published_at' => now(),
        ]);

        // Tandai sudah dibaca
        AnnouncementRead::create([
            'announcement_id' => $ann->id,
            'user_id' => $studentUser->id,
            'read_at' => now(),
        ]);

        // Kunjungi halaman khusus /siswa/pengumuman
        $pageResponse = $this->actingAs($studentUser)->get(route('student.announcements'));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Pengumuman Bisa Dibuka Ulang');

        // Buka modal detail via AJAX (show endpoint)
        $detailResponse = $this->actingAs($studentUser)->getJson(route('student.announcements.show', $ann->id));
        $detailResponse->assertStatus(200);
        $detailResponse->assertJson([
            'id' => $ann->id,
            'title' => 'Pengumuman Bisa Dibuka Ulang',
            'content' => 'Dapat dibuka kembali di halaman pengumuman kapan saja',
        ]);
    }
}
