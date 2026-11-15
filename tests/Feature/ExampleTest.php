<?php

namespace Tests\Feature;

use App\Models\SchoolRequest;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Halaman depan publik tampil dengan paket dari katalog, dan halaman login tetap tersedia.
     */
    public function test_landing_page_renders_with_plans_and_demo_form(): void
    {
        SubscriptionPlan::create([
            'code' => 'pro-yearly', 'name' => 'Paket Pro Tahunan', 'price' => 6000000, 'billing_period' => 'yearly',
            'max_students' => 300, 'max_storage_mb' => 20480, 'features' => ['certificate' => true], 'is_active' => true, 'is_popular' => true,
        ]);
        SubscriptionPlan::create([
            'code' => 'hidden-plan', 'name' => 'Paket Tersembunyi', 'price' => 1, 'billing_period' => 'yearly',
            'max_students' => 1, 'max_storage_mb' => 500, 'features' => [], 'is_active' => false,
        ]);

        $response = $this->get('/')->assertOk()->assertSee(route('public.request-demo'));

        if (\App\Http\Controllers\LandingController::SHOW_PRICING) {
            $response->assertSee('Paket Pro Tahunan')
                ->assertSee('Rp 6.000.000')
                ->assertSee('E-sertifikat PDF ber-QR')
                ->assertDontSee('Paket Tersembunyi');
        } else {
            // Seksi harga sedang disembunyikan: tidak ada paket, nominal, maupun tautan #harga.
            $response->assertDontSee('Harga sesuai jumlah siswa')
                ->assertDontSee('Rp 6.000.000')
                ->assertDontSee('href="#harga"', false);
        }

        $this->get('/login')->assertOk();
    }

    public function test_demo_request_from_landing_is_stored_and_redirects_to_form(): void
    {
        $superAdmin = \App\Models\User::create([
            'name' => 'SA', 'email' => 'sa@x.id', 'password' => 'secret-123', 'role' => 'super-admin', 'is_active' => true,
        ]);

        $this->post(route('public.request-demo'), [
            'school_name' => 'SMK Landing', 'npsn' => '55556666', 'contact_name' => 'Pak Humas',
            'email' => 'humas@landing.sch.id', 'phone' => '081234567890', 'estimated_students' => 120,
        ])->assertRedirect(route('landing') . '#demo')->assertSessionHas('success');

        $this->assertDatabaseHas('school_requests', ['npsn' => '55556666', 'status' => 'pending']);

        // Masuk ke inbox Super Admin.
        $notification = $superAdmin->fresh()->unreadNotifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('demo_request_submitted', $notification->data['type']);
        $this->assertStringContainsString('SMK Landing', $notification->data['title']);
        $this->assertStringContainsString('55556666', $notification->data['message']);

        // Pemohon diteruskan ke WhatsApp tim (08xx dinormalisasi ke 628xx) dengan pesan terisi.
        $waUrl = session('demo_whatsapp_url');
        $this->assertStringStartsWith('https://wa.me/6282187093648?text=', $waUrl);
        $message = rawurldecode(substr($waUrl, strpos($waUrl, '=') + 1));
        $this->assertStringContainsString('Sekolah: SMK Landing', $message);
        $this->assertStringContainsString('NPSN: 55556666', $message);
        $this->assertStringContainsString('WhatsApp: 081234567890', $message);

        $this->get('/')->assertSee('Permohonan terkirim')->assertSee('Kirim lewat WhatsApp');
    }

    public function test_demo_whatsapp_number_can_be_changed_from_platform_settings(): void
    {
        $superAdmin = \App\Models\User::create([
            'name' => 'SA', 'email' => 'sa@x.id', 'password' => 'secret-123', 'role' => 'super-admin', 'is_active' => true,
        ]);

        $this->actingAs($superAdmin)->post(route('superadmin.settings.update'), [
            'platform_name' => 'VokaTrack', 'demo_whatsapp_number' => '12345',
        ])->assertSessionHasErrors('demo_whatsapp_number');

        $this->actingAs($superAdmin)->post(route('superadmin.settings.update'), [
            'platform_name' => 'VokaTrack', 'demo_whatsapp_number' => '081311112222',
        ])->assertSessionHas('success');

        auth()->logout();

        $this->post(route('public.request-demo'), [
            'school_name' => 'SMK Nomor Baru', 'npsn' => '99990000', 'contact_name' => 'X',
            'email' => 'x@baru.sch.id', 'phone' => '0812',
        ]);
        $this->assertStringStartsWith('https://wa.me/6281311112222?text=', session('demo_whatsapp_url'));
    }

    public function test_demo_request_honeypot_and_invalid_npsn(): void
    {
        $this->post(route('public.request-demo'), [
            'school_name' => 'Bot', 'npsn' => '77778888', 'contact_name' => 'Bot', 'email' => 'bot@x.id',
            'phone' => '0', 'website' => 'http://spam.example',
        ])->assertSessionHas('success');
        $this->assertSame(0, SchoolRequest::count(), 'Kiriman bot diabaikan diam-diam');

        $this->post(route('public.request-demo'), [
            'school_name' => 'SMK Salah', 'npsn' => 'ABCDEFGH', 'contact_name' => 'X', 'email' => 'x@x.id', 'phone' => '0',
        ])->assertSessionHasErrors('npsn');
    }
}
