<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Pengerasan login OTP (P0-5/P0-6): normalisasi seragam, attempts terhitung,
 * rate-limit verifikasi, dan login berhasil dengan kode benar.
 */
class OtpLoginTest extends TestCase
{
    use DatabaseTransactions;

    public function test_normalisasi_nomor_seragam(): void
    {
        $this->assertSame('628123456789', PhoneNumber::normalize('08123456789'));
        $this->assertSame('628123456789', PhoneNumber::normalize('8123456789'));
        $this->assertSame('628123456789', PhoneNumber::normalize('+628123456789'));
        $this->assertSame('628123456789', PhoneNumber::normalize('62 812-3456-789'));
    }

    public function test_kode_salah_menaikkan_attempts_dan_tidak_login(): void
    {
        $phone = '628990001234';
        RateLimiter::clear('otp-verify:'.$phone);
        $otp = OtpCode::create(['phone' => $phone, 'code' => '111111', 'expires_at' => now()->addMinutes(5)]);

        $this->post(route('warga.otp.verify.submit'), ['contact' => '08990001234', 'code' => '999999'])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, $otp->fresh()->attempts);
        $this->assertGuest();
    }

    public function test_kode_benar_berhasil_login(): void
    {
        Bus::fake();
        $phone = '628990005678';
        RateLimiter::clear('otp-verify:'.$phone);
        OtpCode::create(['phone' => $phone, 'code' => '222222', 'expires_at' => now()->addMinutes(5)]);

        // Kirim 'contact' dalam format 08xx — harus dinormalisasi ke 628xx saat verify.
        $this->post(route('warga.otp.verify.submit'), ['contact' => '08990005678', 'code' => '222222'])
            ->assertRedirect();

        $this->assertAuthenticated();
        $this->assertNotNull(User::where('phone', $phone)->first());
    }

    public function test_rate_limit_verifikasi_setelah_5_percobaan(): void
    {
        $phone = '628990009999';
        RateLimiter::clear('otp-verify:'.$phone);
        OtpCode::create(['phone' => $phone, 'code' => '333333', 'expires_at' => now()->addMinutes(5)]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('warga.otp.verify.submit'), ['contact' => $phone, 'code' => '000000']);
        }
        // Percobaan ke-6 harus diblok rate limiter (pesan "Terlalu banyak percobaan").
        $resp = $this->post(route('warga.otp.verify.submit'), ['contact' => $phone, 'code' => '333333']);
        $resp->assertSessionHasErrors('code');
        $this->assertGuest();

        RateLimiter::clear('otp-verify:'.$phone);
    }
}
