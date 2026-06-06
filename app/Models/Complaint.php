<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'code', 'channel', 'reporter_name', 'reporter_contact',
        'is_anonymous', 'subject', 'content', 'status',
        'assigned_to_id', 'response', 'responded_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'responded_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public static function generateCode(): string
    {
        $year = now()->format('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('ADU-%s-%04d', $year, $count);
    }
}
