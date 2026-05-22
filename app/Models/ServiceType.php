<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceType extends Model
{
    protected $fillable = [
        'code', 'slug', 'name', 'description',
        'requirements', 'procedure', 'output',
        'bidang', 'sla_minutes', 'sla_display',
        'icon', 'color', 'is_active', 'is_featured', 'order_no',
    ];

    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'procedure' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
