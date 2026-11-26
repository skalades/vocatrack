<?php

namespace App\Http\Controllers;

use App\Notifications\TestWebPushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:512'],
            'keys.auth' => ['required', 'string', 'max:256'],
            'content_encoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);

        $user = $request->user();
        $user->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
            $validated['content_encoding'] ?? null
        );

        return response()->json([
            'ok' => true,
            'subscribed' => true,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:2000'],
        ]);

        $request->user()->deletePushSubscription($validated['endpoint']);

        return response()->json([
            'ok' => true,
            'subscribed' => false,
        ]);
    }

    public function sendTest(Request $request): JsonResponse
    {
        $user = $request->user();

        if (blank(config('webpush.vapid.public_key')) || blank(config('webpush.vapid.private_key'))) {
            return response()->json([
                'ok' => false,
                'message' => 'Kunci VAPID Web Push belum dikonfigurasi di server.',
            ], 503);
        }

        if (! $user->pushSubscriptions()->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'Perangkat ini belum terdaftar menerima notifikasi push.',
            ], 422);
        }

        $user->notifyNow(new TestWebPushNotification());

        return response()->json([
            'ok' => true,
            'message' => 'Notifikasi uji coba telah dikirim ke perangkat Anda.',
        ]);
    }
}
