<?php

namespace App\Http\Controllers;

use App\Models\QueueTicket;
use App\Models\ServiceType;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    public function index()
    {
        $services = ServiceType::active()->orderBy('order_no')->get();

        return view('public.kiosk.index', compact('services'));
    }

    public function takeTicket(Request $request)
    {
        $data = $request->validate([
            'service_type_id' => 'required|exists:service_types,id',
            'walk_in_name' => 'required|string|max:100',
            'walk_in_phone' => 'nullable|string|max:20',
            'priority' => 'nullable|in:normal,prioritas',
        ]);

        $prefix = ($data['priority'] ?? 'normal') === 'prioritas' ? 'P' : 'A';

        // Race-safe: pakai generator terpusat QueueTicket::createNext (retry on conflict).
        $ticket = QueueTicket::createNext([
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'waiting',
            'walk_in_name' => $data['walk_in_name'],
            'walk_in_phone' => $data['walk_in_phone'] ?? null,
        ], $prefix);

        return view('public.kiosk.ticket', compact('ticket'));
    }
}
