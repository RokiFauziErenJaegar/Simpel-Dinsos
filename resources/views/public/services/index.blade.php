@extends('layouts.public')
@section('title', 'Daftar Layanan')
@section('content')

<section class="bg-gradient-to-b from-slate-50 to-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-12">
        <h1 class="text-3xl md:text-4xl font-bold text-slate-900">Daftar Layanan Publik</h1>
        <p class="mt-2 text-slate-600">{{ $services->count() }} layanan tersedia · Semuanya gratis · Mengikuti SOP resmi 2023</p>

        <form method="get" class="mt-6 grid md:grid-cols-4 gap-3">
            <div class="md:col-span-2 relative">
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari layanan…" class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
                <span class="absolute left-4 top-3.5 text-slate-400">🔍</span>
            </div>
            <select name="bidang" class="px-4 py-3 rounded-xl border border-slate-200 bg-white">
                <option value="">Semua Bidang</option>
                @foreach($bidangs as $b)
                    <option value="{{ $b }}" {{ $bidang === $b ? 'selected' : '' }}>{{ $b }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary justify-center">Cari</button>
        </form>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 md:px-6 py-12">
    @if($services->count() === 0)
        <div class="text-center py-16 text-slate-500">
            <div class="text-5xl mb-3">🔍</div>
            <p>Tidak ada layanan yang cocok dengan pencarian Anda.</p>
        </div>
    @else
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($services as $service)
                <a href="{{ route('layanan.show', $service->slug) }}" class="card-elev p-6 hover:-translate-y-1 transition flex flex-col group">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-[color:var(--brand)] flex items-center justify-center text-2xl">
                            📋
                        </div>
                        <span class="text-xs font-mono text-slate-400">{{ $service->code }}</span>
                    </div>
                    <h3 class="font-semibold text-slate-900 leading-snug group-hover:text-[color:var(--brand)]">{{ $service->name }}</h3>
                    <p class="text-sm text-slate-500 mt-2 flex-grow line-clamp-3">{{ $service->description }}</p>
                    <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">⏱ {{ $service->sla_display }}</span>
                        <span class="text-slate-500">🏛 {{ $service->bidang }}</span>
                        <span class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded-full font-medium">Gratis</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>

@endsection
