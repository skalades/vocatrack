<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\WhatsAppGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayController extends Controller
{
    protected function getGatewayBaseUrl(): string
    {
        $rawUrl = config('services.whatsapp.url', 'http://127.0.0.1:3005/send-message');
        $parsed = parse_url($rawUrl);
        if (!empty($parsed['host'])) {
            $scheme = $parsed['scheme'] ?? 'http';
            $port = !empty($parsed['port']) ? ':' . $parsed['port'] : '';
            return "{$scheme}://{$parsed['host']}{$port}";
        }
        return 'http://127.0.0.1:3005';
    }

    public function index(Request $request)
    {
        $token = config('services.whatsapp.token', 'vokatrack-secret-key-2026');
        $provider = config('services.whatsapp.provider', 'custom');

        $isServiceRunning = false;
        $connectionStatus = 'disconnected';
        $qrCodeDataUrl = null;
        $connectedUser = null;
        $error = null;

        $configuredBaseUrl = $this->getGatewayBaseUrl();
        $tryUrls = array_unique([
            $configuredBaseUrl,
            'http://127.0.0.1:3005',
            'http://localhost:3005',
        ]);

        $activeBaseUrl = $configuredBaseUrl;

        foreach ($tryUrls as $url) {
            try {
                $response = Http::timeout(3)->get("{$url}/status");
                if ($response->successful()) {
                    $isServiceRunning = true;
                    $activeBaseUrl = $url;
                    $data = $response->json();
                    $connectionStatus = $data['status'] ?? 'disconnected';
                    $connectedUser = $data['user'] ?? null;
                    $error = $data['error'] ?? null;

                    if ($connectionStatus === 'waiting_scan') {
                        $qrResponse = Http::timeout(3)->get("{$url}/qr-data");
                        if ($qrResponse->successful()) {
                            $qrCodeDataUrl = $qrResponse->json('qr');
                        }
                    }
                    break;
                }
            } catch (\Throwable $e) {
                $error = 'Service gateway lokal tidak merespons: ' . $e->getMessage();
            }
        }

        return view('superadmin.whatsapp', [
            'isServiceRunning' => $isServiceRunning,
            'connectionStatus' => $connectionStatus,
            'qrCodeDataUrl' => $qrCodeDataUrl,
            'connectedUser' => $connectedUser,
            'error' => $error,
            'provider' => $provider,
            'baseUrl' => $activeBaseUrl,
        ]);
    }

    public function ajaxStatus(): JsonResponse
    {
        $configuredBaseUrl = $this->getGatewayBaseUrl();
        $tryUrls = array_unique([
            $configuredBaseUrl,
            'http://127.0.0.1:3005',
            'http://localhost:3005',
        ]);

        foreach ($tryUrls as $url) {
            try {
                $response = Http::timeout(3)->get("{$url}/status");
                if ($response->successful()) {
                    $data = $response->json();
                    $status = $data['status'] ?? 'disconnected';
                    $qr = null;
                    if ($status === 'waiting_scan') {
                        $qrRes = Http::timeout(3)->get("{$url}/qr-data");
                        if ($qrRes->successful()) {
                            $qr = $qrRes->json('qr');
                        }
                    }
                    return response()->json([
                        'running' => true,
                        'status' => $status,
                        'user' => $data['user'] ?? null,
                        'qr' => $qr,
                    ]);
                }
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'running' => false,
            'status' => 'offline',
            'user' => null,
            'qr' => null,
        ]);
    }

    public function testSend(Request $request, WhatsAppGatewayService $gateway)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:25'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $phone = $validated['phone'];
        $message = $validated['message'] ?: '✅ *[VokaTrack]* Uji coba pesan WhatsApp Gateway Mandiri berhasil terkirim!';

        $success = $gateway->sendMessage($phone, $message);

        if ($success) {
            AuditLogService::log(
                'whatsapp_test_send',
                'system',
                "Super Admin mengirim pesan uji coba WhatsApp ke {$phone}."
            );
            return back()->with('success', "Pesan uji coba berhasil dikirim ke nomor {$phone}!");
        }

        return back()->withErrors(['whatsapp' => 'Gagal mengirim pesan. Pastikan WhatsApp sudah terhubung dan nomor tujuan valid.']);
    }

    public function logout()
    {
        $baseUrl = $this->getGatewayBaseUrl();
        try {
            Http::timeout(5)->post("{$baseUrl}/logout");
            return back()->with('success', 'Sesi WhatsApp berhasil diputus. Silakan scan QR ulang.');
        } catch (\Throwable $e) {
            return back()->withErrors(['whatsapp' => 'Gagal memutus sesi WhatsApp: ' . $e->getMessage()]);
        }
    }
}
