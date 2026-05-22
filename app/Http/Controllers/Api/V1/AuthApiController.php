<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\NotificationGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    public function sendOtp(Request $request, NotificationGateway $gateway): JsonResponse
    {
        $data = $request->validate(['phone' => 'required|string|max:20']);
        $phone = $this->normalizePhone($data['phone']);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
        ]);

        // Defer pengiriman OTP setelah response — hindari blocking pada Fonnte HTTP call.
        dispatch(function () use ($phone, $code) {
            try {
                app(NotificationGateway::class)->sendOtp($phone, $code);
            } catch (\Throwable $e) {
                \Log::warning('OTP API send gagal: '.$e->getMessage());
            }
        })->afterResponse();

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

        $otp = OtpCode::where('phone', $phone)
            ->where('code', $data['code'])
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $otp || ! $otp->isValid()) {
            return response()->json(['error' => 'Kode OTP salah atau kedaluwarsa'], 422);
        }

        $otp->update(['used_at' => now()]);

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
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($phone, '08')) return '628'.substr($phone, 2);
        if (str_starts_with($phone, '+62')) return substr($phone, 1);
        return $phone;
    }
}
