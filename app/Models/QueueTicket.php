<?php

namespace App\Models;

use App\Events\QueueTicketCalled;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

class QueueTicket extends Model
{
    protected $fillable = [
        'application_id', 'ticket_number', 'ticket_date',
        'counter', 'priority', 'status',
        'walk_in_name', 'walk_in_phone',
        'called_by_id', 'called_at', 'served_at', 'done_at',
    ];

    protected function casts(): array
    {
        return [
            // 'date:Y-m-d' memaksa serialisasi tanpa komponen waktu.
            // Penting untuk SQLite agar konsisten dengan UNIQUE (ticket_number, ticket_date).
            'ticket_date' => 'date:Y-m-d',
            'called_at' => 'datetime',
            'served_at' => 'datetime',
            'done_at' => 'datetime',
        ];
    }

    /**
     * Mutator: pastikan ticket_date selalu tersimpan sebagai 'YYYY-MM-DD'
     * (tanpa komponen jam) agar UNIQUE constraint berfungsi konsisten.
     */
    public function setTicketDateAttribute($value): void
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        } elseif (is_string($value) && strlen($value) > 10) {
            // Strip komponen waktu dari string 'YYYY-MM-DD HH:MM:SS' menjadi 'YYYY-MM-DD'
            $value = substr($value, 0, 10);
        }
        $this->attributes['ticket_date'] = $value;
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function calledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by_id');
    }

    /**
     * Cari nomor antrian berikutnya untuk hari tertentu.
     *
     * Toleran terhadap data lama yang mungkin tersimpan dengan format
     * 'YYYY-MM-DD HH:MM:SS' (sebelum fix mutator) — pakai LIKE prefix.
     */
    public static function nextNumber(?string $prefix = 'A', ?Carbon $date = null): string
    {
        $date ??= now();
        $dateStr = $date->format('Y-m-d');

        $count = static::where('ticket_date', 'like', $dateStr.'%')
            ->where('ticket_number', 'like', $prefix.'-%')
            ->count() + 1;

        return sprintf('%s-%03d', $prefix, $count);
    }

    /**
     * Buat tiket dengan nomor berurutan secara AMAN dari race condition.
     * Karena ada UNIQUE(ticket_number, ticket_date), dua proses bersamaan bisa
     * menghitung nomor sama → satu insert akan melanggar UNIQUE. Kita retry
     * menghitung ulang nomor sampai berhasil (maks 5x).
     */
    public static function createNext(array $attributes, ?string $prefix = 'A', ?Carbon $date = null): self
    {
        $date ??= now();
        $attempts = 0;

        do {
            $attempts++;
            try {
                return static::create(array_merge($attributes, [
                    'ticket_number' => static::nextNumber($prefix, $date),
                    'ticket_date' => $date->format('Y-m-d'),
                ]));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempts >= 5) {
                    throw $e;
                }
                usleep(random_int(20000, 80000)); // 20-80ms jitter
            }
        } while ($attempts < 5);
    }

    /**
     * Tandai tiket sebagai sedang dilayani + broadcast event ke TV lobi.
     *
     * Dispatch event di-defer ke afterResponse — jadi tetap dikirim ke Reverb,
     * tapi kalau Reverb server mati / lambat, response HTTP user TIDAK menunggu.
     */
    public function callToCounter(?string $counter = null, ?int $calledByUserId = null): void
    {
        $this->update([
            'status' => 'serving',
            'counter' => $counter ?? $this->counter ?? 'LOKET 1',
            'called_by_id' => $calledByUserId,
            'called_at' => now(),
            'served_at' => now(),
        ]);

        $ticketId = $this->id;
        dispatch(function () use ($ticketId) {
            try {
                $ticket = static::find($ticketId);
                if ($ticket) {
                    QueueTicketCalled::dispatch($ticket);
                }
            } catch (\Throwable $e) {
                \Log::warning('Broadcast QueueTicketCalled gagal: '.$e->getMessage());
            }
        })->afterResponse();
    }
}
