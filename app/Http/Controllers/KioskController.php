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

        $ticket = QueueTicket::create([
            'ticket_number' => $this->nextNumber($prefix),
            'ticket_date' => today()->toDateString(),
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'waiting',
            'walk_in_name' => $data['walk_in_name'],
            'walk_in_phone' => $data['walk_in_phone'] ?? null,
        ]);

        return view('public.kiosk.ticket', compact('ticket'));
    }

    protected function nextNumber(string $prefix): string
    {
        $count = QueueTicket::whereDate('ticket_date', today())
            ->where('ticket_number', 'like', $prefix.'-%')
            ->count() + 1;
        return sprintf('%s-%03d', $prefix, $count);
    }
}
