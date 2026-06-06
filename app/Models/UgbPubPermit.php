<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UgbPubPermit extends Model
{
    protected $table = 'ugb_pub_permits';

    protected $fillable = [
        'permit_number', 'type', 'organization',
        'pic_name', 'pic_phone', 'pic_email',
        'legal_form', 'akta_notaris', 'npwp', 'nib',
        'purpose', 'start_date', 'end_date',
        'area_scope', 'target_amount', 'collection_method', 'distribution_plan',
        'kecamatan_id', 'location_address',
        'status', 'reviewed_by_id', 'reviewed_at', 'review_notes', 'rekomendasi_file_path',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'reviewed_at' => 'datetime',
            'target_amount' => 'integer',
        ];
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public static function generateNumber(string $type): string
    {
        $year = now()->format('Y');
        $count = static::where('type', $type)->whereYear('created_at', $year)->count() + 1;

        return sprintf('%s/%03d/D.04/%s', $type, $count, $year);
    }
}
