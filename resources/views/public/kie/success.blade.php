@extends('layouts.public')
@section('title', 'Pendaftaran Konsultasi Berhasil')
@section('content')

<section class="max-w-2xl mx-auto px-4 md:px-6 py-14">
    <div class="card-elev p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto mb-4">✓</div>
        <h1 class="text-2xl font-bold text-slate-900">Pendaftaran Konsultasi Berhasil</h1>
        <p class="text-slate-500 mt-2">Konfirmasi telah dikirim ke WhatsApp Anda. Silakan menuju petugas untuk memulai konsultasi.</p>

        <div class="mt-6 inline-flex flex-col items-center gap-1 px-6 py-4 rounded-xl bg-slate-50 border border-slate-200">
            <span class="text-xs text-slate-500 uppercase tracking-wide">Nomor Registrasi</span>
            <span class="text-2xl font-mono font-bold text-[color:var(--brand)]">{{ $kie->code }}</span>
        </div>

        <div class="mt-4 text-sm text-slate-600 space-y-1">
            <div><span class="text-slate-400">Nama:</span> <strong>{{ $kie->name }}</strong></div>
            @if($kie->location)
                <div><span class="text-slate-400">Lokasi:</span> {{ $kie->location->label() }}</div>
            @endif
            <div><span class="text-slate-400">Waktu:</span> {{ $kie->created_at->translatedFormat('d F Y · H:i') }} WIB</div>
        </div>
    </div>

    {{-- Tampilan minimalis: capaian KIE pada hari pendaftaran ini dibuat --}}
    <div class="card-elev p-5 mt-6 flex items-center gap-4">
        <div class="w-14 h-14 rounded-xl brand-gradient flex items-center justify-center text-white text-2xl font-bold flex-shrink-0">{{ $todayCount }}</div>
        <div>
            <div class="font-semibold text-slate-900 leading-tight">Konsultasi (KIE) hari ini</div>
            <div class="text-sm text-slate-500">Total {{ $todayCount }} konsultasi tercatat pada {{ $kie->created_at->translatedFormat('d F Y') }}.</div>
        </div>
    </div>

    <div class="text-center mt-8">
        <a href="{{ route('home') }}" class="btn-outline">Kembali ke Beranda</a>
    </div>
</section>

@endsection
