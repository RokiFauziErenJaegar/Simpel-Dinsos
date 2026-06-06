<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Application extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'code', 'service_type_id', 'applicant_user_id', 'current_handler_id',
        'beneficiary_name', 'beneficiary_nik', 'beneficiary_relation',
        'purpose', 'status', 'current_step', 'priority',
        'submitted_at', 'sla_due_at', 'completed_at',
        'rejection_reason', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'completed_at' => 'datetime',
            'meta' => 'array',
            'status' => ApplicationStatus::class,
            'beneficiary_nik' => 'encrypted',
        ];
    }

    /** Maskar NIK untuk tampilan (mis. 187103********0001) */
    public function getBeneficiaryNikMaskedAttribute(): ?string
    {
        if (! $this->beneficiary_nik) return null;
        $nik = $this->beneficiary_nik;
        if (strlen($nik) < 16) return $nik;
        return substr($nik, 0, 6) . str_repeat('*', 6) . substr($nik, -4);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function currentHandler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_handler_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApplicationLog::class)->orderBy('created_at');
    }

    public function queueTicket(): HasOne
    {
        return $this->hasOne(QueueTicket::class);
    }

    public function outputDocument(): HasOne
    {
        return $this->hasOne(OutputDocument::class);
    }

    public function isOverdue(): bool
    {
        if (! $this->sla_due_at || $this->status->isFinal()) {
            return false;
        }
        return $this->sla_due_at->isPast();
    }

    public function slaProgress(): float
    {
        if (! $this->submitted_at || ! $this->sla_due_at) {
            return 0;
        }
        $end = $this->status->isFinal() ? ($this->completed_at ?? now()) : now();
        $total = $this->submitted_at->diffInMinutes($this->sla_due_at);
        $elapsed = $this->submitted_at->diffInMinutes($end);
        return $total > 0 ? min(100, ($elapsed / $total) * 100) : 0;
    }

    /**
     * Format kode pengajuan: {SERVICE_CODE}-{YEAR}-{SEQ4}
     * Contoh: L02-2026-0001, L12-2026-0023.
     *
     * Pakai service_type.code (L01..L16 yang unik) sebagai prefix agar tidak bentrok
     * antar layanan dengan slug serupa (mis. rekom-bpjs vs rekom-cota → keduanya REKOM).
     *
     * Sequence diambil dari MAX(suffix) — termasuk row soft-deleted — agar tidak
     * tabrakan dengan kode yang pernah dipakai walau record sudah di-trash.
     */
    public static function generateCode(ServiceType $serviceType): string
    {
        $prefix = $serviceType->code ?: strtoupper(substr($serviceType->slug, 0, 5));
        $year = now()->format('Y');
        $pattern = $prefix.'-'.$year.'-%';

        $lastCode = static::withTrashed()
            ->where('code', 'like', $pattern)
            ->orderByDesc('code')
            ->value('code');

        $nextSeq = $lastCode
            ? ((int) substr($lastCode, -4)) + 1
            : 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $nextSeq);
    }

    public function calculateSlaDueAt(): Carbon
    {
        $minutes = $this->serviceType->sla_minutes ?? 1440;
        return $this->submitted_at->copy()->addMinutes($minutes);
    }

    /**
     * Terapkan hasil review berkas saat pengembalian.
     * $reviews = daftar baris dari form petugas, tiap baris:
     *   ['document_id' => '...', 'invalid' => bool, 'note' => ?string]
     *
     * Berkas yang ditandai `invalid` → is_validated=false + notes (perlu diunggah ulang);
     * berkas lain → is_validated=true (valid).
     */
    public function applyDocumentReview(array $reviews): void
    {
        $reviews = collect($reviews);

        foreach ($this->documents as $doc) {
            $review = $reviews->firstWhere('document_id', (string) $doc->id);
            $invalid = $review && ($review['invalid'] ?? false);

            $doc->update([
                'is_validated' => $invalid ? false : true,
                'notes' => $invalid ? ($review['note'] ?? null) : null,
            ]);
        }
    }
}
