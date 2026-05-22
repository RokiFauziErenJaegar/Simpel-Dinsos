<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PwaController extends Controller
{
    public function deviceTest()
    {
        return view('public.pwa-test');
    }

    public function subscribePush(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string|max:500',
            'keys.p256dh' => 'required|string|max:200',
            'keys.auth' => 'required|string|max:200',
        ]);

        $sub = PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $request->user()?->id,
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth'],
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'last_seen_at' => now(),
            ]
        );

        return response()->json(['ok' => true, 'id' => $sub->id]);
    }

    public function vapidPublicKey(): JsonResponse
    {
        return response()->json([
            'key' => config('services.push.vapid_public_key', null),
            'note' => config('services.push.vapid_public_key')
                ? 'OK'
                : 'VAPID_PUBLIC_KEY belum di-set di .env — push real belum aktif.',
        ]);
    }

    public function testServerPush(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Login dulu untuk test push dari server.'], 401);
        }

        $sent = app(\App\Services\WebPushService::class)->sendToUser(
            $user,
            'SIMPEL DINSOS — Server Push',
            'Notifikasi dari server berhasil. Push real berfungsi bahkan saat tab tertutup. ' . now()->translatedFormat('H:i:s'),
            route('warga.dashboard'),
        );

        return response()->json([
            'ok' => true,
            'sent' => $sent,
            'message' => $sent > 0
                ? "{$sent} notifikasi dikirim dari server ke device Anda."
                : 'Tidak ada subscription aktif. Subscribe push notification dulu di halaman ini.',
        ]);
    }
}
