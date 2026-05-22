<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutputDocument extends Model
{
    protected $fillable = [
        'application_id', 'document_number', 'file_path',
        'verification_token', 'signed_by_user_id', 'signed_at', 'file_hash',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }

    public function verificationUrl(): string
    {
        return route('document.verify', ['token' => $this->verification_token]);
    }
}
