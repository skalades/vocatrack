<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherMobileLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_teacher_can_switch_to_dedicated_mobile_layout(): void
    {
        $user = User::where('role', 'guru-pembimbing')->first();
        if (!$user) {
            $this->markTestSkipped('Guru pembimbing user not seeded');
        }

        // 1. Explicit ?view=mobile switch
        $response = $this->actingAs($user)->get(route('guru.dashboard', ['view' => 'mobile']));
        $response->assertStatus(200);
        $response->assertSee('data-teacher-shell', false);
        $response->assertSee('aria-label="Navigasi Cepat Guru"', false);
        $response->assertSee('title="Beralih ke Tampilan Laptop/Desktop"', false);
        $response->assertSee('Portal Guru Pembimbing', false);
        $response->assertSee('id="live-clock-digits"', false);
        $response->assertDontSee('title="Kembali ke Dashboard"', false);
        $response->assertSee('Hadir Hari Ini', false);
        $response->assertSee('Rekap Kehadiran Hari Ini', false);
        $response->assertSee('Perhatian Hari Ini', false);
        $response->assertSee('Buka Monitoring Lengkap', false);

        // 2. Session should persist mobile layout on subsequent visit without query param
        $followUp = $this->actingAs($user)->get(route('guru.monitoring'));
        $followUp->assertStatus(200);
        $followUp->assertSee('data-teacher-shell', false);
        $followUp->assertSee('title="Kembali ke Dashboard"', false);
        $followUp->assertSee('Live Presensi', false);
        $followUp->assertSee('Monitoring Presensi', false);
        $followUp->assertSee('id="mobileDateFilterForm"', false);
        $followUp->assertSee('Hadir Tepat', false);

        $journalsMobile = $this->actingAs($user)->get(route('guru.journals'));
        $journalsMobile->assertStatus(200);
        $journalsMobile->assertSee('data-teacher-shell', false);
        $journalsMobile->assertSee('title="Kembali ke Dashboard"', false);
        $journalsMobile->assertSee('Review Jurnal (Tingkat 2)', false);
        $journalsMobile->assertSee('Logbook Jurnal PKL', false);
        $journalsMobile->assertSee('Antrean Butuh Validasi', false);
        $journalsMobile->assertSee('id="m-tab-btn-students"', false);
        $journalsMobile->assertSee('id="m-view-students"', false);

        $requestsMobile = $this->actingAs($user)->get(route('guru.requests'));
        $requestsMobile->assertStatus(200);
        $requestsMobile->assertSee('data-teacher-shell', false);
        $requestsMobile->assertSee('title="Kembali ke Dashboard"', false);
        $requestsMobile->assertSee('Izin & Sakit Siswa', false);
        $requestsMobile->assertSee('Menunggu Keputusan', false);
        $requestsMobile->assertSee('id="mobile-requests-container"', false);

        // 3. Explicit ?view=desktop switch
        $desktopRes = $this->actingAs($user)->get(route('guru.dashboard', ['view' => 'desktop']));
        $desktopRes->assertStatus(200);
        $desktopRes->assertDontSee('data-teacher-shell', false);
        $desktopRes->assertDontSee('id="live-clock-digits"', false);
        $desktopRes->assertSee('id="sidebar-menu"', false);

        $desktopMon = $this->actingAs($user)->get(route('guru.monitoring'));
        $desktopMon->assertStatus(200);
        $desktopMon->assertDontSee('id="mobileDateFilterForm"', false);
        $desktopMon->assertSee('Total Log Kehadiran', false);

        $desktopJournals = $this->actingAs($user)->get(route('guru.journals'));
        $desktopJournals->assertStatus(200);
        $desktopJournals->assertDontSee('id="m-tab-btn-students"', false);
        $desktopJournals->assertSee('id="tab-btn-students"', false);

        $desktopRequests = $this->actingAs($user)->get(route('guru.requests'));
        $desktopRequests->assertStatus(200);
        $desktopRequests->assertDontSee('id="mobile-requests-container"', false);
        $desktopRequests->assertSee('Total Permohonan', false);
    }

    public function test_teacher_auto_detects_mobile_user_agent(): void
    {
        $user = User::where('role', 'guru-pembimbing')->first();
        if (!$user) {
            $this->markTestSkipped('Guru pembimbing user not seeded');
        }

        // Clear session
        session()->forget('teacher_view_mode');

        // Request with iPhone User-Agent
        $response = $this->actingAs($user)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1'])
            ->get(route('guru.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('data-teacher-shell', false);
        $response->assertSee('aria-label="Navigasi Cepat Guru"', false);
    }
}
