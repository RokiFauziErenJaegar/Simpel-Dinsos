<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatisfactionSurvey extends Model
{
    protected $fillable = [
        'application_id',
        'persyaratan', 'prosedur', 'waktu', 'biaya', 'produk',
        'kompetensi', 'perilaku', 'sarana', 'penanganan_pengaduan',
        'saran', 'respondent_name', 'respondent_contact', 'submitted_at',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** Indeks Kepuasan Masyarakat = (rata-rata 9 unsur / 5) * 100 */
    public function getIndexAttribute(): float
    {
        $items = collect([
            $this->persyaratan, $this->prosedur, $this->waktu,
            $this->biaya, $this->produk, $this->kompetensi,
            $this->perilaku, $this->sarana, $this->penanganan_pengaduan,
        ])->filter(fn ($v) => $v !== null);

        if ($items->isEmpty()) {
            return 0;
        }

        return round(($items->avg() / 5) * 100, 2);
    }
}
