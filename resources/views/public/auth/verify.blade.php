@extends('layouts.public')
@section('title', 'Verifikasi OTP')
@section('content')

<section class="max-w-md mx-auto px-4 md:px-6 py-16">
    <div class="card-elev p-8">
        <div class="text-center">
            <div class="text-5xl mb-2">💬</div>
            <h1 class="text-2xl font-bold text-slate-900">Masukkan Kode OTP</h1>
            <p class="text-slate-600 text-sm mt-1">
                Kami kirim via WhatsApp ke <strong>{{ $masked }}</strong>
            </p>
        </div>

        @if(session('success'))
            <div class="mt-4 p-3 bg-emerald-50 text-emerald-700 text-sm rounded-lg">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mt-4 p-3 bg-rose-50 text-rose-700 text-sm rounded-lg">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('warga.otp.verify.submit') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="contact" value="{{ $contact }}">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Kode 6 digit</label>
                <input name="code" type="text" maxlength="6" required inputmode="numeric"
                       autocomplete="one-time-code" autofocus
                       class="w-full px-4 py-4 rounded-xl border-2 border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none text-center text-2xl tracking-[0.5em] font-bold">
            </div>
            <button type="submit" class="btn-primary w-full justify-center">Verifikasi →</button>
        </form>

        <div class="mt-4 flex items-center justify-between text-sm">
            <a href="{{ route('warga.login') }}" class="text-slate-500 hover:text-slate-700">‹ Ganti nomor</a>
            <span class="text-slate-400">Berlaku 5 menit</span>
        </div>
    </div>
</section>

@endsection
