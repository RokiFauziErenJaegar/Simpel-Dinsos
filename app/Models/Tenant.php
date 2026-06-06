<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'slug', 'name', 'kode_wilayah', 'instansi',
        'alamat', 'kode_pos', 'call_center', 'email',
        'maklumat', 'kop_logo', 'primary_color',
        'settings', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** Tenant aktif saat ini berdasarkan resolver. */
    public static function current(): ?self
    {
        return app()->bound('tenant.current') ? app('tenant.current') : null;
    }

    /** Resolve tenant dari subdomain (mis. pringsewu.simpel-dinsos.id). */
    public static function fromSubdomain(?string $host): ?self
    {
        if (! $host) {
            return null;
        }
        $parts = explode('.', $host);
        if (count($parts) < 3) {
            return null;
        }

        return static::where('slug', $parts[0])->where('is_active', true)->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
