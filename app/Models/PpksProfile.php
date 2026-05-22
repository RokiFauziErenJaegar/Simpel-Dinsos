<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpksProfile extends Model
{
    protected $table = 'ppks_profiles';

    protected $fillable = [
        'user_id',
        'birth_date',
        'birth_place',
        'gender',
        'occupation',
        'family_card_no',
        'dtsen_desil',
        'dtsen_verified_at',
        'photo_path',
        'house_photo_path',
        'categories',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'dtsen_verified_at' => 'date',
            'categories' => 'array',
            'family_card_no' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIsDtsenAttribute(): bool
    {
        return $this->dtsen_desil !== null && $this->dtsen_desil >= 1 && $this->dtsen_desil <= 10;
    }
}
