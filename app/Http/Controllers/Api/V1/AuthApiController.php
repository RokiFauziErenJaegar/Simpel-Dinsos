<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Jobs\SendOtpJob;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\NotificationGateway;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthApiController extends Controller
{
    public function sendOtp(Request $request, NotificationGateway $gateway): JsonResponse
    {
        $data = $request->validate(['phone' => 'required|string|max:20']);
        $phone = $this->normalizePhone($data['phone']);

        // Rate-limit pengiriman OTP per nomor (anti SMS/WA bombing).
        $key = 'otp-send:'.$phone;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'error' => 'Terlalu banyak permintaan OTP. Coba lagi dalam '.ceil(RateLimiter::availableIn($key) / 60).' menit.',
            ], 429);
        }
        RateLimiter::hit($key, 900);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
        ]);

        // Push ke queue worker (lihat WargaAuthController::sendOtp untuk alasan).
        SendOtpJob::dispatch($phone, $code);

        return response()->json([
            'ok' => true,
            'message' => 'Kode OTP dikirim ke '.$phone,
            'expires_in' => 300,
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'code' => 'required|string|size:6',
            'device_name' => 'required|string|max:100',
        ]);

        $phone = $this->normalizePhone($data['phone']);

        // Rate-limit verifikasi per nomor (anti brute-force OTP).
        $key = 'otp-verify:'.$phone;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'error' => 'Terlalu banyak percobaan. Coba lagi dalam '.ceil(RateLimiter::availableIn($key) / 60).' menit.',
            ], 429);
        }

        // Ambil OTP terbaru tanpa filter kode → attempts terhitung walau kode salah.
        $otp = OtpCode::where('phone', $phone)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $otp || ! $otp->isValid() || ! hash_equals($otp->code, $data['code'])) {
            RateLimiter::hit($key, 900);
            if ($otp && ! $otp->used_at) {
                $otp->increment('attempts');
            }

            return response()->json(['error' => 'Kode OTP salah atau kedaluwarsa'], 422);
        }

        $otp->update(['used_at' => now()]);
        RateLimiter::clear($key);

        $user = User::firstOrCreate(
            ['phone' => $phone],
            [
                'name' => 'Warga '.substr($phone, -4),
                'email' => 'warga'.substr($phone, -8).'@warga.test',
                'password' => Hash::make(str()->random(16)),
                'role' => UserRole::Warga->value,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $token = $user->createToken($data['device_name'], ['warga'])->plainTextToken;

        return response()->json([
            'ok' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role->value,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role->value,
            'kecamatan' => $user->kecamatan?->name,
            'pekon' => $user->pekon?->name,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }

    protected function normalizePhone(string $phone): string
    {
        return PhoneNumber::normalize($phone);
    }
}
