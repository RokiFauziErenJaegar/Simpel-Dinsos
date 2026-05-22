<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    protected Google2FA $g;

    public function __construct()
    {
        $this->g = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->g->generateSecretKey(32);
    }

    public function qrCodeUrl(User $user, string $secret): string
    {
        // Standar otpauth:// URL untuk Google Authenticator / Authy
        $issuer = rawurlencode('SIMPEL DINSOS');
        $account = rawurlencode($user->email);
        return "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}";
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->g->verifyKey($secret, $code);
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))).'-'.strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }
}
