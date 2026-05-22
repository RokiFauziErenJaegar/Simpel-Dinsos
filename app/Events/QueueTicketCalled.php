<?php

namespace App\Events;

use App\Models\QueueTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Implements ShouldBroadcastNow (bukan ShouldBroadcast) agar dispatch SYNC tanpa
 * pakai queue. Tapi pemanggilannya WAJIB dibungkus try/catch + dispatchAfterResponse
 * di sisi pemanggil (QueueTicket::callToCounter) agar tidak blocking HTTP response
 * jika Reverb server sedang mati.
 */
class QueueTicketCalled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $ticketNumber;
    public ?string $counter;
    public ?string $beneficiaryName;

    public function __construct(QueueTicket $ticket)
    {
        $this->ticketNumber = $ticket->ticket_number;
        $this->counter = $ticket->counter ?? 'LOKET 1';
        $this->beneficiaryName = $ticket->application?->beneficiary_name
            ?? $ticket->walk_in_name;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('antrian');
    }

    public function broadcastAs(): string
    {
        return 'ticket.called';
    }
}
