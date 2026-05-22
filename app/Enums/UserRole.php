<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Kadis = 'kadis';
    case Sekretaris = 'sekretaris';
    case Kabid = 'kabid';
    case Kasi = 'kasi';
    case Petugas = 'petugas';
    case OperatorPekon = 'operator_pekon';
    case Warga = 'warga';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Kadis => 'Kepala Dinas',
            self::Sekretaris => 'Sekretaris',
            self::Kabid => 'Kepala Bidang',
            self::Kasi => 'Kepala Seksi',
            self::Petugas => 'Petugas Loket',
            self::OperatorPekon => 'Operator Pekon/Kelurahan',
            self::Warga => 'Warga',
        };
    }

    public function isInternal(): bool
    {
        return ! in_array($this, [self::Warga, self::OperatorPekon]);
    }

    public function canAccessFilament(): bool
    {
        return $this !== self::Warga;
    }
}
