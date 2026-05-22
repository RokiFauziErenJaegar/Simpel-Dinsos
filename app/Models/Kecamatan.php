<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kecamatan extends Model
{
    protected $fillable = ['code', 'name'];

    public function pekons(): HasMany
    {
        return $this->hasMany(Pekon::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
