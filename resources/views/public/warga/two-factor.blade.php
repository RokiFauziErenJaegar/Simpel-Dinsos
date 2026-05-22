@extends('layouts.public')
@section('title', 'Autentikasi Dua Faktor')
@section('content')

<section class="max-w-2xl mx-auto px-4 md:px-6 py-10">
    <h1 class="text-2xl font-bold text-slate-900">🔐 Autentikasi Dua Faktor (2FA)</h1>
    <p class="text-slate-600 text-sm mt-1">
        Lapisan keamanan tambahan untuk akun {{ $user->role->label() }}.
        @if($user->twoFactorRequired())
            <strong class="text-rose-600">Wajib aktif</strong> sesuai SOP keamanan untuk role ini.
        @endif
    </p>

    @if(session('success'))
        <div class="mt-4 card-elev p-4 border-l-4 border-emerald-500 bg-emerald-50 text-emerald-800 text-sm">✓ {{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="mt-4 card-elev p-4 border-l-4 border-rose-500 bg-rose-50 text-rose-700 text-sm">{{ $errors->first() }}</div>
    @endif

    {{-- Status --}}
    <div class="mt-6 card-elev p-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-slate-700">Status 2FA</div>
                @if($user->hasTwoFactorEnabled())
                    <div class="text-emerald-600 font-bold mt-1">✓ Aktif sejak {{ $user->two_factor_confirmed_at->translatedFormat('d M Y H:i') }}</div>
                @else
                    <div class="text-amber-600 font-bold mt-1">⚠ Belum aktif</div>
                @endif
            </div>
            @if($user->hasTwoFactorEnabled())
                <form method="post" action="{{ route('two-factor.disable') }}" onsubmit="return confirm('Nonaktifkan 2FA? Akun Anda akan lebih rentan.')">
                    @csrf
                    <button class="btn-outline text-sm">Nonaktifkan</button>
                </form>
            @endif
        </div>
    </div>

    @if(! $user->hasTwoFactorEnabled())
        <div class="mt-6 card-elev p-6">
            <h2 class="font-bold text-slate-900">Langkah Aktivasi</h2>
            <ol class="mt-3 space-y-2 text-sm text-slate-700 list-decimal pl-5">
                <li>Pasang aplikasi <strong>Google Authenticator</strong>, <strong>Authy</strong>, atau <strong>Microsoft Authenticator</strong> di HP Anda.</li>
                <li>Pindai QR code di bawah dengan aplikasi tersebut.</li>
                <li>Ketik kode 6 digit yang muncul untuk konfirmasi.</li>
            </ol>

            <div class="mt-6 grid md:grid-cols-2 gap-6 items-start">
                <div class="text-center">
                    <div class="inline-block p-3 bg-white border-2 border-slate-200 rounded-xl">
                        {!! $qrSvg !!}
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Atau masukkan kode manual:<br><code class="bg-slate-100 px-2 py-1 rounded text-xs">{{ $secret }}</code></p>
                </div>
                <form method="post" action="{{ route('two-factor.confirm') }}">
                    @csrf
                    <label class="block text-sm font-medium text-slate-700 mb-2">Kode 6 digit dari authenticator</label>
                    <input name="code" type="text" maxlength="6" required inputmode="numeric" autocomplete="one-time-code" autofocus
                           class="w-full px-4 py-4 rounded-xl border-2 border-slate-200 focus:border-[color:var(--brand)] outline-none text-center text-2xl tracking-[0.5em] font-bold">
                    <button class="btn-primary w-full justify-center mt-4">Aktifkan 2FA →</button>
                </form>
            </div>
        </div>
    @endif

    @if($user->hasTwoFactorEnabled() && $recoveryCodes)
        <div class="mt-6 card-elev p-6 bg-amber-50 border-l-4 border-amber-500">
            <h2 class="font-bold text-amber-900">🔑 Recovery Codes</h2>
            <p class="text-sm text-amber-800 mt-1">Simpan kode ini di tempat aman. Setiap kode hanya dapat digunakan sekali jika Anda kehilangan akses ke authenticator.</p>
            <div class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm">
                @foreach($recoveryCodes as $code)
                    <div class="px-3 py-2 bg-white rounded-lg border border-amber-200">{{ $code }}</div>
                @endforeach
            </div>
            <p class="text-xs text-amber-700 mt-3">Tersisa <strong>{{ count($recoveryCodes) }}</strong> kode. Hubungi DPO untuk regenerasi jika habis.</p>
        </div>
    @endif
</section>

@endsection
