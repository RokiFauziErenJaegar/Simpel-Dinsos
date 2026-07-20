<?php

namespace App\Http\Controllers;

use App\Enums\ServiceLocation;
use App\Jobs\SendKieNotificationJob;
use App\Models\KieConsultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Konsultasi Warga (KIE) — pendaftaran mandiri oleh warga sebelum konsultasi.
 *
 * Alur: warga isi data diri + no. WA → dapat nomor registrasi → sistem kirim
 * pesan WA konfirmasi. Modul ini TERPISAH dari 16 layanan (counter sendiri).
 * Lokasi bisa di-set lewat ?loc=dinsos|mpp (mis. QR yang dipasang di tiap tempat).
 */
class KieController extends Controller
{
    public function create(Request $request)
    {
        $loc = ServiceLocation::tryFrom((string) $request->query('loc', ''));
        $formNonce = (string) Str::uuid();
        $todayCount = KieConsultation::countForDate();

        return view('public.kie.create', [
            'presetLocation' => $loc,
            'formNonce' => $formNonce,
            'todayCount' => $todayCount,
        ]);
    }

    public function store(Request $request)
    {
        // Anti-duplikat (fitur 1) — replay guard nonce.
        $nonce = trim((string) $request->input('form_nonce', ''));
        if ($nonce !== '' && ($existingCode = Cache::get('kieform:'.$nonce))) {
            return redirect()->route('kie.sukses', ['code' => $existingCode]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:20',
            'nik' => 'nullable|string|size:16',
            'address' => 'nullable|string|max:255',
            'topic' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'location' => 'nullable|string|in:dinsos,mpp',
            'consent' => 'required|accepted',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'phone.required' => 'Nomor WhatsApp wajib diisi agar konfirmasi dapat dikirim.',
            'consent.accepted' => 'Mohon setujui pernyataan data sebelum mengirim.',
        ]);

        $create = function () use ($data, $request, $nonce) {
            if ($nonce !== '' && ($existingCode = Cache::get('kieform:'.$nonce))) {
                return $existingCode;
            }

            $kie = new KieConsultation([
                'name' => $data['name'],
                'phone' => $this->normalizePhone($data['phone']),
                'nik' => $data['nik'] ?? null,
                'address' => $data['address'] ?? null,
                'topic' => $data['topic'] ?? null,
                'description' => $data['description'] ?? null,
                'location' => $data['location'] ?? null,
                'status' => 'registered',
            ]);

            // Kode unik: retry kalau bentrok.
            $attempts = 0;
            do {
                $attempts++;
                try {
                    $kie->code = KieConsultation::generateCode();
                    $kie->save();
                    break;
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    if ($attempts >= 3) {
                        throw $e;
                    }
                    usleep(50000);
                }
            } while ($attempts < 3);

            SendKieNotificationJob::dispatch($kie->id);

            if ($nonce !== '') {
                Cache::put('kieform:'.$nonce, $kie->code, now()->addMinutes(30));
            }

            return $kie->code;
        };

        $code = $nonce !== ''
            ? Cache::lock('kieform-lock:'.$nonce, 30)->block(20, $create)
            : $create();

        return redirect()->route('kie.sukses', ['code' => $code]);
    }

    public function success(string $code)
    {
        $kie = KieConsultation::where('code', $code)->firstOrFail();
        $todayCount = KieConsultation::countForDate($kie->created_at);

        return view('public.kie.success', compact('kie', 'todayCount'));
    }

    /** Normalisasi nomor: 08xxx → 628xxx (selaras dgn gateway). */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($phone, '08')) {
            return '628'.substr($phone, 2);
        }
        if (str_starts_with($phone, '+62')) {
            return substr($phone, 1);
        }
        if (str_starts_with($phone, '8')) {
            return '62'.$phone;
        }

        return $phone;
    }
}
