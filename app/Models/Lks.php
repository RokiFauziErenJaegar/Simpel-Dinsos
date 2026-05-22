<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lks extends Model
{
    use BelongsToTenant;

    protected $table = 'lks';

    protected $fillable = [
        'registration_number', 'name', 'type', 'address', 'kecamatan_id',
        'contact_person', 'phone', 'email',
        'akta_notaris', 'npwp', 'kemenkumham_no',
        'registered_at', 'valid_until', 'client_count', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }
}
