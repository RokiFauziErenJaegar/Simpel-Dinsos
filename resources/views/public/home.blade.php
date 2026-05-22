@extends('layouts.public')
@section('title', 'Beranda')
@section('content')

{{-- HERO --}}
<section class="brand-gradient text-white">
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-16 md:py-24 grid md:grid-cols-2 gap-12 items-center">
        <div>
            <span class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full text-xs font-medium backdrop-blur">
                ⬢ Layanan Sosial Digital Pertama di Pringsewu
            </span>
            <h1 class="mt-4 text-4xl md:text-5xl font-extrabold leading-tight">
                Halo, Warga Pringsewu 👋
            </h1>
            <p class="mt-3 text-2xl md:text-3xl font-bold text-white/95">
                Layanan Sosial dalam Genggaman Anda
            </p>
            <p class="mt-4 text-white/80 text-lg max-w-md">
                {{ $allCount }} jenis layanan publik · 100% gratis · Tanpa antre berjam-jam.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('layanan.index') }}" class="bg-white text-[color:var(--brand)] px-6 py-3 rounded-xl font-semibold hover:bg-slate-100 transition inline-flex items-center gap-2">
                    Ajukan Layanan →
                </a>
                <a href="{{ route('cek-status.index') }}" class="border border-white/40 text-white px-6 py-3 rounded-xl font-semibold hover:bg-white/10 transition">
                    Cek Status Saya
                </a>
            </div>
        </div>
        <div class="relative">
            <div class="bg-white/10 rounded-3xl p-6 backdrop-blur border border-white/20">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-3xl font-bold">{{ $stats['served_today'] }}</div>
                        <div class="text-xs text-white/70 mt-1">Dilayani Hari Ini</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold">{{ $stats['waiting'] }}</div>
                        <div class="text-xs text-white/70 mt-1">Antrian Aktif</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold">{{ $stats['completed_month'] }}</div>
                        <div class="text-xs text-white/70 mt-1">Selesai Bulan Ini</div>
                    </div>
                </div>
                <div class="mt-6 pt-6 border-t border-white/20">
                    <div class="text-sm font-semibold mb-3">Sedang Dilayani</div>
                    @if($nowServing->count())
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($nowServing as $ticket)
                                <div class="bg-white text-[color:var(--brand)] rounded-2xl p-4 text-center">
                                    <div class="text-3xl font-extrabold">{{ $ticket->ticket_number }}</div>
                                    <div class="text-xs text-slate-500 mt-1">{{ $ticket->counter ?? 'Loket 1' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-white/70 text-sm">Belum ada antrian dilayani saat ini.</div>
                    @endif
                    @if($upcoming->count())
                        <div class="mt-4 text-xs text-white/70">
                            Berikutnya: {{ $upcoming->pluck('ticket_number')->join(' · ') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

{{-- LAYANAN POPULER --}}
<section class="max-w-7xl mx-auto px-4 md:px-6 py-16">
    <div class="flex items-end justify-between mb-8">
        <div>
            <h2 class="text-2xl md:text-3xl font-bold text-slate-900">Layanan Paling Dicari</h2>
            <p class="text-slate-600 mt-1">Pilih layanan, lengkapi berkas, pantau status — semua dari HP Anda.</p>
        </div>
        <a href="{{ route('layanan.index') }}" class="text-sm font-semibold text-[color:var(--brand)] hover:underline hidden md:inline">Lihat semua →</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($featuredServices as $service)
            <a href="{{ route('layanan.show', $service->slug) }}" class="card-elev p-5 hover:-translate-y-1 transition group">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-[color:var(--brand)] flex items-center justify-center text-2xl mb-3">
                    @switch($service->icon)
                        @case('identification') 🪪 @break
                        @case('shield-check') 🏥 @break
                        @case('heart') ❤️ @break
                        @case('user-group') ♿ @break
                        @case('megaphone') 📢 @break
                        @default 📋
                    @endswitch
                </div>
                <div class="font-semibold text-slate-900 leading-snug group-hover:text-[color:var(--brand)]">
                    {{ \Illuminate\Support\Str::limit($service->name, 50) }}
                </div>
                <div class="mt-3 flex items-center justify-between text-xs">
                    <span class="text-slate-500">⏱ {{ $service->sla_display }}</span>
                    <span class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded-full font-medium">Gratis</span>
                </div>
            </a>
        @endforeach
    </div>

    <div class="md:hidden mt-6 text-center">
        <a href="{{ route('layanan.index') }}" class="btn-outline">Lihat {{ $allCount }} Layanan</a>
    </div>
</section>

{{-- ALUR LAYANAN --}}
<section class="bg-slate-50 py-16">
    <div class="max-w-7xl mx-auto px-4 md:px-6">
        <h2 class="text-2xl md:text-3xl font-bold text-slate-900 text-center mb-12">Cara Mengajukan Layanan</h2>
        <div class="grid md:grid-cols-5 gap-4">
            @foreach([
                ['1', 'Daftar / Masuk', 'Verifikasi via OTP WhatsApp'],
                ['2', 'Pilih Layanan', 'Dari 16 layanan tersedia'],
                ['3', 'Lengkapi Berkas', 'Upload KTP, KK, surat pengantar'],
                ['4', 'Pantau Status', 'Real-time + notifikasi WA'],
                ['5', 'Surat Terbit', 'Unduh PDF ber-QR resmi'],
            ] as $step)
                <div class="card-elev p-5 text-center relative">
                    <div class="w-12 h-12 mx-auto rounded-full brand-gradient text-white flex items-center justify-center font-bold text-lg">{{ $step[0] }}</div>
                    <div class="mt-3 font-semibold text-slate-900">{{ $step[1] }}</div>
                    <div class="text-sm text-slate-500 mt-1">{{ $step[2] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- MOTTO --}}
<section class="max-w-7xl mx-auto px-4 md:px-6 py-16">
    <div class="card-elev p-10 brand-gradient text-white text-center">
        <div class="text-4xl font-extrabold tracking-wider">C · A · R · E</div>
        <div class="mt-2 text-white/90 font-medium">Cepat · Adaptif · Responsif · Empati</div>
        <p class="mt-6 italic text-white/80 text-lg">"Mudah, cepat, dan tanpa biaya."</p>
    </div>
</section>

@endsection
