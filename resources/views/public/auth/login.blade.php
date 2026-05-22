@extends('layouts.public')
@section('title', 'Masuk')
@section('content')

<section class="max-w-md mx-auto px-4 md:px-6 py-16">
    <div class="card-elev p-8">
        <h1 class="text-2xl font-bold text-slate-900">Masuk ke Akun Anda</h1>
        <p class="text-slate-600 text-sm mt-1">Verifikasi cepat lewat kode OTP yang dikirim ke nomor WhatsApp Anda.</p>

        @if(session('success'))
            <div class="mt-4 p-3 bg-emerald-50 text-emerald-700 text-sm rounded-lg">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mt-4 p-3 bg-rose-50 text-rose-700 text-sm rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="post" action="{{ route('warga.otp.send') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nomor WhatsApp aktif</label>
                <div class="flex items-center gap-2 px-4 py-3 rounded-xl border border-slate-200 focus-within:border-[color:var(--brand)] focus-within:ring-2 focus-within:ring-blue-100">
                    <span class="text-lg">💬</span>
                    <input
                        name="contact"
                        type="tel"
                        required
                        placeholder="08xxxxxxxxxx"
                        value="{{ old('contact') }}"
                        class="flex-1 outline-none bg-transparent">
                </div>
                <p class="text-xs text-slate-500 mt-1.5">
                    Pastikan nomor terdaftar di WhatsApp aktif. Kode OTP akan dikirim langsung via WA.
                </p>
            </div>

            <button type="submit" class="btn-primary w-full justify-center">
                Kirim OTP ke WhatsApp →
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-xs text-slate-500 space-y-2">
            <div class="flex items-start gap-2">
                <span>🔒</span>
                <span>Data Anda dilindungi sesuai <strong>UU PDP 27/2022</strong>. NIK & KK disimpan terenkripsi.</span>
            </div>
            <div class="flex items-start gap-2">
                <span>⏱</span>
                <span>Kode OTP berlaku 5 menit. Maksimal 5 percobaan per 15 menit.</span>
            </div>
        </div>
    </div>

    <div class="mt-4 text-center text-sm text-slate-500">
        Butuh bantuan? Hubungi <a href="https://wa.me/6282269867911" class="text-[color:var(--brand)] font-semibold">0822-6986-7911</a>
    </div>
</section>

@endsection
