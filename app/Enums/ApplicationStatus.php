<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case InVerification = 'in_verification';
    case AwaitingDisposition = 'awaiting_disposition';
    case InProcess = 'in_process';
    case FieldVerification = 'field_verification';
    case AwaitingSignature = 'awaiting_signature';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Diajukan',
            self::InVerification => 'Sedang Diverifikasi',
            self::AwaitingDisposition => 'Menunggu Disposisi',
            self::InProcess => 'Sedang Diproses',
            self::FieldVerification => 'Verifikasi Lapangan',
            self::AwaitingSignature => 'Menunggu Tanda Tangan',
            self::Completed => 'Selesai',
            self::Rejected => 'Ditolak',
            self::Returned => 'Dikembalikan ke Pemohon',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted, self::InVerification, self::AwaitingDisposition, self::InProcess, self::FieldVerification, self::AwaitingSignature => 'warning',
            self::Completed => 'success',
            self::Rejected => 'danger',
            self::Returned => 'info',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Rejected]);
    }
}
