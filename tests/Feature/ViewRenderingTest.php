<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->actingAs($user)->get(route('superadmin.schools'))->assertStatus(200)->assertSee('Daftar Tenant Sekolah');
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
    }

    public function test_student_views_render_successfully(): void
    {
        $user = User::where('email', 'siswa@smkn2garut.sch.id')->first() ?? User::where('role', 'siswa')->first();
        if (!$user) {
            $this->markTestSkipped('Siswa user not seeded');
        }

        $this->actingAs($user)->get(route('student.home'))->assertStatus(200)->assertSee('Portal Siswa PKL');
        $this->actingAs($user)->get(route('student.attendance'))->assertStatus(200)->assertSee('Presensi Harian');
        $this->actingAs($user)->get(route('student.journals'))->assertStatus(200)->assertSee('Jurnal Harian PKL');
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
    }
}
