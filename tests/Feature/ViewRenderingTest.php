<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ViewRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('VokaTrack');
        $response->assertSee('Masuk ke Sistem');
    }

    public function test_superadmin_views_render_successfully(): void
    {
        $user = User::where('email', 'superadmin@vokatrack.id')->first();
        if (!$user) {
            $this->markTestSkipped('Superadmin user not seeded');
        }

        $this->actingAs($user)->get(route('superadmin.dashboard'))->assertStatus(200)->assertSee('VokaTrack');
        $this->actingAs($user)->get(route('superadmin.schools'))->assertStatus(200)->assertSee('Tenant');
        $this->actingAs($user)->get(route('superadmin.invoices'))->assertStatus(200)->assertSee('Tagihan & Invoice');
        $this->actingAs($user)->get(route('superadmin.plans'))->assertStatus(200)->assertSee('Katalog Paket');
        $this->actingAs($user)->get(route('superadmin.settings'))->assertStatus(200)->assertSee('Logo Utama Sistem');
    }

    public function test_superadmin_can_update_and_reset_system_logo(): void
    {
        Storage::fake('public');
        $user = User::where('email', 'superadmin@vokatrack.id')->first();
        if (!$user) {
            $this->markTestSkipped('Superadmin user not seeded');
        }

        $fakeLogo = UploadedFile::fake()->image('custom_logo.png', 200, 200);

        // 1. Upload custom logo
        $response = $this->actingAs($user)->post(route('superadmin.settings.update'), [
            'platform_name' => 'VokaTrack Pro',
            'platform_tagline' => 'Cloud Edition',
            'system_logo' => $fakeLogo,
        ]);

        $response->assertSessionHas('success');
        $this->assertNotNull(PlatformSetting::get('system_logo'));
        $this->assertStringContainsString('storage/branding', PlatformSetting::getLogoUrl());

        // 2. Reset back to default logo
        $responseReset = $this->actingAs($user)->post(route('superadmin.settings.update'), [
            'platform_name' => 'VokaTrack',
            'reset_logo' => 1,
        ]);

        $responseReset->assertSessionHas('success');
        $this->assertNull(PlatformSetting::get('system_logo'));
        $this->assertEquals(asset('images/logo.png'), PlatformSetting::getLogoUrl());
    }

    public function test_admin_sekolah_views_render_successfully(): void
    {
        $user = User::where('email', 'admin@smkn2garut.sch.id')->first() ?? User::where('role', 'admin-sekolah')->first();
        if (!$user) {
            $this->markTestSkipped('Admin sekolah user not seeded');
        }

        $this->actingAs($user)->get(route('adminsekolah.dashboard'))->assertStatus(200)->assertSee('Dashboard Admin Sekolah');
        $this->actingAs($user)->get(route('adminsekolah.settings'))->assertStatus(200)->assertSee('Konfigurasi Sekolah');
        $this->actingAs($user)->get(route('adminsekolah.majors'))->assertStatus(200)->assertSee('Daftar Jurusan Keahlian');
        $this->actingAs($user)->get(route('adminsekolah.students'))->assertStatus(200)->assertSee('Daftar Siswa Sekolah');
        $this->actingAs($user)->get(route('adminsekolah.teachers'))->assertStatus(200)->assertSee('Daftar Guru Pembimbing');
        $this->actingAs($user)->get(route('adminsekolah.companies'))->assertStatus(200)->assertSee('Dunia Usaha & Dunia Industri');
        $this->actingAs($user)->get(route('adminsekolah.plotting'))->assertStatus(200)->assertSee('Plotting Penugasan Siswa PKL');
        $this->actingAs($user)->get(route('adminsekolah.announcements'))->assertStatus(200)->assertSee('Pengumuman & Siaran Sekolah');
        $this->actingAs($user)->get(route('adminsekolah.holidays'))->assertStatus(200)->assertSee('Kalender Hari Libur');
        $this->actingAs($user)->get(route('adminsekolah.incidents'))->assertStatus(200)->assertSee('Pusat Monitoring Kendala');
        $this->actingAs($user)->get(route('adminsekolah.certificates'))->assertStatus(200)->assertSee('E-Sertifikat & Transkrip');
        $this->actingAs($user)->get(route('adminsekolah.parent-links'))->assertStatus(200)->assertSee('Kelola Link Portal Monitoring Orang Tua');
        $this->actingAs($user)->get(route('adminsekolah.map'))->assertStatus(200)->assertSee('Peta Sebaran Mitra DU/DI');
    }

    public function test_student_views_render_successfully(): void
    {
        $user = User::where('email', 'siswa@smkn2garut.sch.id')->first() ?? User::where('role', 'siswa')->first();
        if (!$user) {
            $this->markTestSkipped('Siswa user not seeded');
        }

        $this->actingAs($user)->get(route('student.home'))->assertStatus(200)->assertSee('Portal Siswa PKL');
        $this->actingAs($user)->get(route('student.attendance'))->assertStatus(200)->assertSee('Check-In Hari Ini');
        $this->actingAs($user)->get(route('student.journals'))->assertStatus(200)->assertSee('Riwayat Jurnal');
        $this->actingAs($user)->get(route('student.profile'))->assertStatus(200)->assertSee('Informasi Penempatan PKL');
        $this->actingAs($user)->get(route('student.announcements'))->assertStatus(200)->assertSee('Papan Pengumuman PKL');
    }

    public function test_teacher_views_render_successfully(): void
    {
        $user = User::where('email', 'guru@smkn2garut.sch.id')->first() ?? User::where('role', 'guru-pembimbing')->first();
        if (!$user) {
            $this->markTestSkipped('Guru user not seeded');
        }

        $this->actingAs($user)->get(route('guru.dashboard'))->assertStatus(200)->assertSee('Dashboard Guru Pembimbing');
        $this->actingAs($user)->get(route('guru.monitoring'))->assertStatus(200)->assertSee('Monitoring Presensi Siswa Binaan');
        $this->actingAs($user)->get(route('guru.requests'))->assertStatus(200)->assertSee('Verifikasi Izin & Sakit Siswa');
        $this->actingAs($user)->get(route('guru.journals'))->assertStatus(200)->assertSee('Review Jurnal PKL (Tingkat 2)');
        $this->actingAs($user)->get(route('guru.assessments'))->assertStatus(200)->assertSee('Penilaian Akademik PKL');
        $this->actingAs($user)->get(route('guru.reports'))->assertStatus(200)->assertSee('Dokumen Laporan PDF Siswa PKL');
        $this->actingAs($user)->get(route('guru.announcements'))->assertStatus(200)->assertSee('Pengumuman Bimbingan PKL');
        $this->actingAs($user)->get(route('guru.incidents'))->assertStatus(200)->assertSee('Laporan Kendala');
        $this->actingAs($user)->get(route('guru.supervisions'))->assertStatus(200)->assertSee('Supervisi & Berita Acara');
        $this->actingAs($user)->get(route('guru.parent-links'))->assertStatus(200)->assertSee('Link Monitoring Orang Tua Siswa');
        $this->actingAs($user)->get(route('guru.map'))->assertStatus(200)->assertSee('Peta Sebaran Siswa Bimbingan PKL');
    }

    public function test_dudi_views_render_successfully(): void
    {
        $user = User::where('email', 'supervisor@antam.com')->first() ?? User::where('role', 'pembimbing-dudi')->first();
        if (!$user) {
            $this->markTestSkipped('DU/DI user not seeded');
        }

        $this->actingAs($user)->get(route('dudi.dashboard'))->assertStatus(200)->assertSee('Dashboard Pembimbing Industri DU/DI');
        $this->actingAs($user)->get(route('dudi.attendances'))->assertStatus(200)->assertSee('Validasi Presensi Harian Siswa Magang');
        $this->actingAs($user)->get(route('dudi.requests'))->assertStatus(200)->assertSee('Verifikasi Dinas Luar & WFH');
        $this->actingAs($user)->get(route('dudi.journals'))->assertStatus(200)->assertSee('Approval Jurnal Magang (Tingkat 1)');
        $this->actingAs($user)->get(route('dudi.assessments'))->assertStatus(200)->assertSee('Penilaian Praktik Industri (DU/DI)');
    }

    public function test_kepsek_views_render_successfully(): void
    {
        $user = User::where('email', 'kepsek@smkn2garut.sch.id')->first() ?? User::where('role', 'kepala-sekolah')->first();
        if (!$user) {
            $this->markTestSkipped('Kepsek user not seeded');
        }

        $this->actingAs($user)->get(route('kepsek.dashboard'))->assertStatus(200)->assertSee('Dashboard Eksekutif PKL');
        $this->actingAs($user)->get(route('kepsek.rekap'))->assertStatus(200)->assertSee('Rekapitulasi Presensi Matriks Bulanan');
        $this->actingAs($user)->get(route('kepsek.map'))->assertStatus(200)->assertSee('Peta Sebaran PKL');
    }

    public function test_notifications_view_renders_for_users(): void
    {
        $teacher = User::where('role', 'guru-pembimbing')->first();
        if ($teacher) {
            $this->actingAs($teacher)->get(route('notifications.index'))->assertStatus(200)->assertSee('Pusat Pemberitahuan');
        }

        $student = User::where('role', 'siswa')->first();
        if ($student) {
            $this->actingAs($student)->get(route('notifications.index'))->assertStatus(200)->assertSee('Pusat Pemberitahuan');
        }
    }
}
