<?php

namespace App\Models\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Scope;

/**
 * Trait untuk model yang multi-tenant aware.
 *
 * Otomatis:
 *   - filter query berdasarkan tenant aktif (global scope)
 *   - isi tenant_id saat creating (jika belum di-set)
 *
 * Mode 'single' (default Pringsewu): scope tidak aktif, tetap berfungsi normal.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        // Auto-set tenant_id saat creating
        static::creating(function (Model $model) {
            if (! $model->tenant_id) {
                $tenant = Tenant::current();
                if ($tenant) {
                    $model->tenant_id = $tenant->id;
                }
            }
        });

        // Global scope filter — hanya aktif kalau mode != single
        if (config('tenant.mode', 'single') !== 'single') {
            static::addGlobalScope(new class implements Scope {
                public function apply(Builder $builder, Model $model): void
                {
                    $tenant = Tenant::current();
                    if ($tenant) {
                        $builder->where($model->getTable().'.tenant_id', $tenant->id);
                    }
                }
            });
        }
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
