<?php

namespace App\Models;

use App\Enums\ServiceLocation;
use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, \Laravel\Sanctum\HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'nik',
        'nik_hash',
        'phone',
        'address',
        'kecamatan_id',
        'pekon_id',
        'is_active',
        'location',
        'last_login_at',
        'signature_path',
        'stamp_path',
        'jabatan_full',
        'nip',
        'pangkat',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role' => UserRole::class,
            'location' => ServiceLocation::class,
            'nik' => 'encrypted',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    public function twoFactorRequired(): bool
    {
        // Semua peran internal (admin, kadis, sekretaris, kabid, kasi, petugas)
        // wajib 2FA karena dapat melakukan aksi sensitif di panel.
        return (bool) $this->role?->isInternal();
    }

    /**
     * Mutator: setiap kali NIK di-set, hash deterministik dihitung
     * untuk lookup unique tanpa expose plaintext NIK.
     */
    public function setNikAttribute($value): void
    {
        $this->attributes['nik'] = $value ? Crypt::encryptString($value) : null;
        $this->attributes['nik_hash'] = $value ? static::hashNik($value) : null;
    }

    public static function hashNik(string $nik): string
    {
        return hash_hmac('sha256', $nik, config('app.key'));
    }

    public static function findByNik(string $nik): ?self
    {
        return static::where('nik_hash', static::hashNik($nik))->first();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->role->canAccessFilament();
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function pekon(): BelongsTo
    {
        return $this->belongsTo(Pekon::class);
    }

    public function ppksProfile(): HasOne
    {
        return $this->hasOne(PpksProfile::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'applicant_user_id');
    }

    public function isWarga(): bool
    {
        return $this->role === UserRole::Warga;
    }

    public function isKadis(): bool
    {
        return $this->role === UserRole::Kadis;
    }
}
