<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WhatsAppGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SuperAdminWhatsAppGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.provider' => 'custom',
            'services.whatsapp.url' => 'http://127.0.0.1:3005/send-message',
            'services.whatsapp.token' => 'vokatrack-secret-key-2026',
        ]);
        WhatsAppGatewayService::resetFake();

        $this->superAdmin = User::create([
            'name' => 'Super Administrator Platform',
            'email' => 'superadmin@vokatrack.test',
            'password' => Hash::make('password123'),
            'role' => 'super-admin',
            'is_active' => true,
        ]);
    }

    public function test_superadmin_can_access_whatsapp_gateway_page(): void
    {
        Http::fake([
            'http://127.0.0.1:3005/status' => Http::response([
                'status' => 'waiting_scan',
                'user' => null,
            ], 200),
            'http://127.0.0.1:3005/qr-data' => Http::response([
                'status' => 'waiting_scan',
                'qr' => 'data:image/png;base64,sampleqr',
            ], 200),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.whatsapp'));

        $response->assertOk();
        $response->assertSee('WhatsApp Gateway & Bot SOS');
        $response->assertSee('Scan QR Code Sekarang');
        $response->assertSee('sampleqr');
    }

    public function test_superadmin_can_poll_whatsapp_status_via_ajax(): void
    {
        Http::fake([
            'http://127.0.0.1:3005/status' => Http::response([
                'status' => 'connected',
                'user' => ['id' => '6281234567890'],
            ], 200),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.whatsapp.status'));

        $response->assertOk();
        $response->assertJson([
            'running' => true,
            'status' => 'connected',
            'user' => ['id' => '6281234567890'],
        ]);
    }

    public function test_superadmin_can_send_test_whatsapp_message(): void
    {
        Http::fake([
            'http://127.0.0.1:3005/send-message' => Http::response(['success' => true], 200),
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.whatsapp.test'), [
            'phone' => '08123456789',
            'message' => 'Uji coba pesan',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
