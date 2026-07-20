<?php

namespace App\Enums;

/**
 * Lokasi/kanal pelaksanaan pelayanan.
 *
 * SIMPEL DINSOS dipakai di dua tempat fisik yang berbeda:
 *  - dinsos : Kantor Dinas Sosial Kabupaten Pringsewu
 *  - mpp    : Mal Pelayanan Publik (MPP)
 *
 * Lokasi pelayanan pada sebuah pengajuan/KIE ditentukan mengikuti
 * lokasi akun petugas yang menangani (di-stamp saat petugas beraksi).
 * Untuk pengajuan online yang belum pernah disentuh petugas, lokasi
 * tetap null → ditampilkan sebagai "Online / Belum diproses".
 */
enum ServiceLocation: string
{
    case Dinsos = 'dinsos';
    case Mpp = 'mpp';

    public function label(): string
    {
        return match ($this) {
            self::Dinsos => 'Kantor Dinas Sosial',
            self::Mpp => 'Mal Pelayanan Publik (MPP)',
        };
    }

    /** Label pendek untuk badge/kolom sempit. */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Dinsos => 'Dinsos',
            self::Mpp => 'MPP',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Dinsos => 'info',
            self::Mpp => 'warning',
        };
    }

    /** Opsi untuk select Filament / dropdown: ['dinsos' => 'Kantor Dinas Sosial', ...]. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
