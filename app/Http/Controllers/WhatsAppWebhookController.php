<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Endpoint webhook untuk inbound message dari gateway WA.
     *
     * Format payload disesuaikan Fonnte / Wablas:
     *   - sender: nomor pengirim (628xxx)
     *   - message: isi pesan
     *
     * Untuk demo, juga bisa dipanggil langsung dari Postman/curl.
     */
    public function inbound(Request $request, WhatsAppBot $bot)
    {
        // Verifikasi token — FAIL CLOSED. Jika token belum dikonfigurasi, tolak
        // request (jangan biarkan endpoint terbuka tanpa proteksi).
        $expected = config('services.notifications.webhook_token');
        if (! $expected) {
            Log::warning('[WA-INBOUND] Ditolak: NOTIFICATION_WEBHOOK_TOKEN belum di-set.');

            return response()->json(['error' => 'Webhook belum dikonfigurasi'], 503);
        }
        $given = $request->header('X-Webhook-Token') ?? $request->input('token');
        if (! hash_equals($expected, (string) $given)) {
            return response()->json(['error' => 'Token tidak valid'], 401);
        }

        $payload = $request->all();

        // Coba beberapa nama field umum Fonnte/Wablas
        $sender = $payload['sender'] ?? $payload['from'] ?? $payload['phone'] ?? null;
        $message = $payload['message'] ?? $payload['text'] ?? $payload['body'] ?? null;

        if (! $sender || ! $message) {
            return response()->json(['error' => 'Payload tidak lengkap', 'expected_fields' => ['sender', 'message']], 422);
        }

        $reply = $bot->handle($sender, $message);

        Log::info('[WA-INBOUND] '.$sender.' -> '.substr($message, 0, 80));

        return response()->json([
            'ok' => true,
            'reply' => $reply,
        ]);
    }

    /**
     * Endpoint test/demo untuk simulasikan inbound dari browser.
     */
    public function simulate(Request $request, WhatsAppBot $bot)
    {
        // Hanya untuk lingkungan non-produksi (demo/test).
        abort_unless(app()->environment(['local', 'testing']), 404);

        $data = $request->validate([
            'sender' => 'required|string|max:20',
            'message' => 'required|string|max:500',
        ]);

        $reply = $bot->handle($data['sender'], $data['message']);

        return response()->json([
            'sender' => $data['sender'],
            'message' => $data['message'],
            'reply' => $reply,
        ]);
    }
}
