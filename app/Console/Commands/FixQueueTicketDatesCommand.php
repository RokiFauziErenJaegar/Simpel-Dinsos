<?php

namespace App\Console\Commands;

use App\Models\QueueTicket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Normalisasi kolom queue_tickets.ticket_date dari format
 * 'YYYY-MM-DD HH:MM:SS' (datetime) ke 'YYYY-MM-DD' (date saja).
 *
 * Fix bug pre-2025 di mana cast 'date' (tanpa format) menyimpan datetime
 * di SQLite, menyebabkan UNIQUE (ticket_number, ticket_date) tidak
 * berfungsi konsisten dengan query nextNumber().
 *
 * Aman dijalankan berulang.
 */
class FixQueueTicketDatesCommand extends Command
{
    protected $signature = 'queue:fix-dates {--dry-run : Hanya tampilkan rencana}';

    protected $description = 'Normalisasi ticket_date queue_tickets ke format Y-m-d';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        // SQLite-friendly: cari baris dengan panjang ticket_date > 10
        $affected = QueueTicket::query()
            ->whereRaw('LENGTH(ticket_date) > 10')
            ->get();

        if ($affected->isEmpty()) {
            $this->info('✓ Tidak ada baris ticket_date yang perlu dinormalisasi.');
            return self::SUCCESS;
        }

        $this->info(($dry ? '[DRY-RUN] ' : '').'Menormalkan '.$affected->count().' baris ticket_date…');

        foreach ($affected as $t) {
            $before = (string) $t->getRawOriginal('ticket_date');
            $after = substr($before, 0, 10);
            $this->line("  - #{$t->id} {$t->ticket_number}: {$before} → {$after}");

            if (! $dry) {
                // Pakai DB::table untuk hindari trigger mutator/cast loop
                DB::table('queue_tickets')->where('id', $t->id)->update(['ticket_date' => $after]);
            }
        }

        if (! $dry) {
            $this->info('✓ Selesai. Sekarang query nextNumber() konsisten dengan data tersimpan.');
        } else {
            $this->warn('Jalankan tanpa --dry-run untuk eksekusi.');
        }

        return self::SUCCESS;
    }
}
