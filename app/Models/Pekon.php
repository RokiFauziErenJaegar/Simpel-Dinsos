<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pekon extends Model
{
    protected $fillable = ['kecamatan_id', 'name', 'type'];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return ucfirst($this->type).' '.$this->name;
    }
}
