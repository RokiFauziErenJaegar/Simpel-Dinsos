@extends('layouts.public')
@section('title', 'Verifikasi 2FA')
@section('content')

<section class="max-w-md mx-auto px-4 md:px-6 py-16">
    <div class="card-elev p-8">
        <div class="text-center">
            <div class="text-5xl mb-2">🔐</div>
            <h1 class="text-2xl font-bold text-slate-900">Verifikasi 2FA</h1>
            <p class="text-slate-600 text-sm mt-1">Masukkan kode 6 digit dari aplikasi authenticator Anda, atau salah satu recovery code.</p>
        </div>

        @if($errors->any())
            <div class="mt-4 p-3 bg-rose-50 text-rose-700 text-sm rounded-lg">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('two-factor.verify-challenge') }}" class="mt-6">
            @csrf
            <input name="code" type="text" maxlength="20" required autofocus autocomplete="one-time-code"
                   placeholder="000000 atau recovery code"
                   class="w-full px-4 py-4 rounded-xl border-2 border-slate-200 focus:border-[color:var(--brand)] outline-none text-center text-xl tracking-widest font-bold">
            <button class="btn-primary w-full justify-center mt-4">Verifikasi →</button>
        </form>
    </div>
</section>

@endsection
