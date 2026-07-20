@extends('layouts.public')
@section('title', 'Statistik Kepuasan Masyarakat')
@section('content')

@php
    $catColor = match($stats['category']) {
        'SANGAT BAIK' => '#059669',
        'BAIK' => '#2563eb',
        'CUKUP' => '#d97706',
        'PERLU PERBAIKAN' => '#dc2626',
        default => '#64748b',
    };
@endphp

<section class="brand-gradient text-white">
    <div class="max-w-5xl mx-auto px-4 md:px-6 py-10">
        <a href="{{ route('home') }}" class="text-sm text-white/70 hover:text-white mb-3 inline-block">‹ Beranda</a>
        <h1 class="text-2xl md:text-3xl font-bold">Statistik Kepuasan Masyarakat</h1>
        <p class="text-white/80 mt-1">Survei Kepuasan Masyarakat (SKM) — Permenpan RB 14/2017 · Periode {{ $label }}</p>
    </div>
</section>

<section class="max-w-5xl mx-auto px-4 md:px-6 py-8">

    {{-- Pemilih periode --}}
    <div class="flex flex-wrap items-center gap-2 mb-6">
        <a href="{{ route('skm.stats', ['range' => 'bulan']) }}" class="px-4 py-2 rounded-lg text-sm font-medium border {{ $range === 'bulan' ? 'bg-[color:var(--brand)] text-white border-transparent' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' }}">Bulan Ini</a>
        <a href="{{ route('skm.stats', ['range' => 'tahun']) }}" class="px-4 py-2 rounded-lg text-sm font-medium border {{ $range === 'tahun' ? 'bg-[color:var(--brand)] text-white border-transparent' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' }}">Tahun Ini</a>
        <form method="get" action="{{ route('skm.stats') }}" class="flex flex-wrap items-center gap-2 ml-auto">
            <input type="hidden" name="range" value="kustom">
            <input type="date" name="from" value="{{ request('from', $from->toDateString()) }}" class="px-3 py-2 rounded-lg border border-slate-200 text-sm">
            <span class="text-slate-400 text-sm">s/d</span>
            <input type="date" name="to" value="{{ request('to', $to->toDateString()) }}" class="px-3 py-2 rounded-lg border border-slate-200 text-sm">
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium bg-slate-800 text-white hover:bg-slate-900">Terapkan</button>
        </form>
    </div>

    {{-- Ringkasan utama --}}
    <div class="grid md:grid-cols-3 gap-4 mb-6">
        <div class="card-elev p-6">
            <div class="text-sm text-slate-500">Total Responden (periode)</div>
            <div class="text-4xl font-bold text-slate-900 mt-1">{{ number_format($stats['total']) }}</div>
            <div class="text-xs text-slate-400 mt-1">Total sepanjang waktu: {{ number_format($allTime) }} responden</div>
        </div>
        <div class="card-elev p-6">
            <div class="text-sm text-slate-500">Indeks Kepuasan (IKM)</div>
            <div class="text-4xl font-bold mt-1" style="color: {{ $catColor }}">{{ $stats['index'] !== null ? number_format($stats['index'], 2) : '—' }}<span class="text-lg text-slate-400"> / 100</span></div>
            <div class="text-xs text-slate-400 mt-1">Skala 0–100</div>
        </div>
        <div class="card-elev p-6 flex flex-col justify-center">
            <div class="text-sm text-slate-500">Mutu Pelayanan</div>
            <div class="inline-flex items-center gap-2 mt-2">
                <span class="px-3 py-1.5 rounded-lg text-sm font-bold text-white" style="background: {{ $catColor }}">{{ $stats['category'] }}</span>
            </div>
        </div>
    </div>

    @if($stats['total'] === 0)
        <div class="card-elev p-8 text-center text-slate-500">Belum ada responden pada periode ini.</div>
    @else
        {{-- Per unsur --}}
        <div class="card-elev p-6 mb-6">
            <h2 class="font-bold text-slate-900 mb-4">Nilai per Unsur Pelayanan</h2>
            <div class="space-y-3">
                @foreach($stats['per_unsur'] as $u)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-700">{{ $u['label'] }}</span>
                            <span class="font-semibold text-slate-900">{{ $u['score'] !== null ? number_format($u['score'], 1) : '—' }}</span>
                        </div>
                        <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full" style="width: {{ $u['score'] ?? 0 }}%; background: {{ $catColor }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Per lokasi (Dinsos vs MPP) --}}
        <div class="card-elev p-6 mb-6">
            <h2 class="font-bold text-slate-900 mb-4">Sebaran per Lokasi Pelayanan</h2>
            <div class="grid sm:grid-cols-3 gap-4">
                @foreach($stats['per_location'] as $loc)
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-sm text-slate-500">{{ $loc['label'] }}</div>
                        <div class="text-2xl font-bold text-slate-900 mt-1">{{ $loc['index'] !== null ? number_format($loc['index'], 1) : '—' }}</div>
                        <div class="text-xs text-slate-400">{{ $loc['total'] }} responden</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Saran terbaru --}}
        @if(count($stats['latest_saran']))
            <div class="card-elev p-6">
                <h2 class="font-bold text-slate-900 mb-4">Saran &amp; Masukan Terbaru</h2>
                <div class="space-y-3">
                    @foreach($stats['latest_saran'] as $s)
                        <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                            <p class="text-sm text-slate-700">“{{ $s['saran'] }}”</p>
                            <div class="text-xs text-slate-400 mt-1">— {{ $s['name'] ?: 'Anonim' }} · {{ $s['at']?->translatedFormat('d M Y') }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</section>

@endsection
