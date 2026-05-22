<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'application_id', 'type', 'label', 'file_path',
        'original_name', 'file_size', 'mime_type',
        'is_validated', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_validated' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
