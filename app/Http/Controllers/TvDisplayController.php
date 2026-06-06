<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\QueueTicket;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TvDisplayController extends Controller
{
    public function display()
    {
        $services = ServiceType::active()->orderBy('order_no')->take(8)->get();

        // Halaman TV no-cache supaya update template (CSS/JS inline) langsung
        // terpakai setelah deploy. Tanpa ini, browser bisa hold versi lama.
        return response()
            ->view('public.tv', compact('services'))
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'CDN-Cache-Control' => 'no-store',
                'Cloudflare-CDN-Cache-Control' => 'no-store',
            ]);
    }

    /**
     * Live data untuk TV lobi. Header `Cache-Control: no-store` mencegah
     * Cloudflare / proxy / browser cache response — antrian harus selalu fresh.
     */
    public function liveData(): JsonResponse
    {
        $today = today()->toDateString();

        // LIKE prefix toleran ke data lama (datetime) DAN baru (date only)
        $todayFilter = fn ($query) => $query->where('ticket_date', 'like', $today.'%');

        $nowServing = QueueTicket::query()
            ->tap($todayFilter)
            ->where('status', 'serving')
            ->orderByDesc('called_at')
            ->take(3)
            ->get(['ticket_number', 'counter', 'called_at']);

        $upcoming = QueueTicket::query()
            ->tap($todayFilter)
            ->where('status', 'waiting')
            ->orderBy('id')
            ->take(8)
            ->pluck('ticket_number');

        $lastCalled = QueueTicket::query()
            ->tap($todayFilter)
            ->whereIn('status', ['serving', 'called'])
            ->orderByDesc('called_at')
            ->first(['ticket_number', 'counter', 'called_at']);

        $stats = [
            'served_today' => QueueTicket::query()->tap($todayFilter)->where('status', 'done')->count(),
            'waiting_today' => QueueTicket::query()->tap($todayFilter)->where('status', 'waiting')->count(),
            'completed_month' => Application::query()
                ->where('status', 'completed')
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)
                ->count(),
        ];

        return response()->json([
            'now_serving' => $nowServing,
            'upcoming' => $upcoming,
            'last_called' => $lastCalled,
            'stats' => $stats,
            'server_time' => now()->format('H:i:s'),
            'server_date' => now()->translatedFormat('l, d F Y'),
            // Cek konsistensi: response_at WAJIB beda setiap polling.
            // Kalau ini selalu sama → response di-cache (CDN/SW/browser).
            'fetched_at' => now()->toIso8601String(),
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            // Cloudflare specific: bypass cache
            'CDN-Cache-Control' => 'no-store',
            'Cloudflare-CDN-Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Endpoint diagnostic untuk troubleshooting TV lobi.
     * Tampilkan raw data + query result untuk verifikasi data ada di DB.
     */
    public function debug(Request $request): JsonResponse
    {
        // Endpoint diagnostik — hanya non-produksi (jangan bocorkan data operasional).
        abort_unless(app()->environment(['local', 'testing']), 404);

        $today = today()->toDateString();

        $sample = QueueTicket::orderByDesc('id')->take(10)->get([
            'id', 'ticket_number', 'ticket_date', 'status', 'application_id', 'created_at',
        ]);

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'today' => $today,
            'today_carbon' => today()->__toString(),
            'last_10_tickets' => $sample->toArray(),
            'queries' => [
                'now_serving_count' => QueueTicket::where('ticket_date', 'like', $today.'%')
                    ->where('status', 'serving')->count(),
                'waiting_count' => QueueTicket::where('ticket_date', 'like', $today.'%')
                    ->where('status', 'waiting')->count(),
                'all_today' => QueueTicket::where('ticket_date', 'like', $today.'%')->count(),
                'all_tickets_total' => QueueTicket::count(),
            ],
            'last_applications' => Application::orderByDesc('id')->take(5)->get([
                'id', 'code', 'status', 'submitted_at',
            ])->toArray(),
        ])->withHeaders(['Cache-Control' => 'no-store']);
    }
}
