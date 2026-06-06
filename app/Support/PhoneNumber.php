<?php

namespace App\Support;

/**
 * Normalisasi nomor telepon Indonesia ke format E.164 tanpa '+' (mis. 628xxxxxxxxx).
 * Satu sumber kebenaran dipakai web (WargaAuthController) maupun API (AuthApiController)
 * agar tidak terjadi identitas ganda akibat normalisasi berbeda.
 */
class PhoneNumber
{
    public static function normalize(string $phone): string
    {
        // Sisakan digit dan tanda '+' saja
        $phone = preg_replace('/[^0-9+]/', '', $phone) ?? '';
        // Buang '+' di depan (E.164 tanpa plus)
        $phone = ltrim($phone, '+');

        if ($phone === '') {
            return '';
        }

        // 0xxxx  → 62xxxx  (08123 → 628123)
        if (str_starts_with($phone, '0')) {
            return '62'.substr($phone, 1);
        }
        // sudah 62xxxx
        if (str_starts_with($phone, '62')) {
            return $phone;
        }
        // 8xxxx (tanpa 0 di depan) → 628xxxx
        if (str_starts_with($phone, '8')) {
            return '62'.$phone;
        }

        return $phone;
    }

    /** Format tampilan tersamar, mis. 6281****789. */
    public static function mask(string $phone): string
    {
        if (strlen($phone) < 6) {
            return $phone;
        }

        return substr($phone, 0, 4).str_repeat('*', strlen($phone) - 7).substr($phone, -3);
    }
}
