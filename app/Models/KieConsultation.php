<?php

namespace App\Models;

use App\Enums\ServiceLocation;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Konsultasi Warga (KIE) — pencatatan warga yang datang berkonsultasi.
 * Counter-nya terpisah dari 16 layanan (tabel sendiri).
 */
class KieConsultation extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'nik',
        'address',
        'topic',
        'description',
        'status',
        'location',
        'handled_by',
        'notified_at',
        'served_at',
        'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'location' => ServiceLocation::class,
            'notified_at' => 'datetime',
            'served_at' => 'datetime',
        ];
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * Nomor registrasi unik: KIE-{TAHUN}-{URUT4}.
     * Pakai withTrashed agar nomor tidak terpakai ulang setelah soft delete.
     */
    public static function generateCode(): string
    {
        $year = now()->year;
        $prefix = "KIE-{$year}-";

        $last = static::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /** Jumlah KIE yang tercatat pada suatu tanggal (default hari ini). */
    public static function countForDate(?\DateTimeInterface $date = null): int
    {
        $date = $date ? \Illuminate\Support\Carbon::instance($date) : now();

        return static::whereDate('created_at', $date->toDateString())->count();
    }
}
