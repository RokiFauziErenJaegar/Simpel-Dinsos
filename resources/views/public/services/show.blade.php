@extends('layouts.public')
@section('title', $service->name)
@section('content')

<section class="bg-slate-50 border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-12">
        <a href="{{ route('layanan.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-4 inline-block">‹ Kembali ke Daftar Layanan</a>
        <div class="flex flex-wrap items-start gap-4">
            <div class="w-16 h-16 rounded-2xl brand-gradient flex items-center justify-center text-3xl text-white">📋</div>
            <div class="flex-1 min-w-0">
                <div class="text-xs font-mono text-slate-500">{{ $service->code }} · Bidang {{ $service->bidang }}</div>
                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mt-1">{{ $service->name }}</h1>
                <p class="text-slate-600 mt-2 max-w-3xl">{{ $service->description }}</p>
            </div>
        </div>
    </div>
</section>

<section class="max-w-7xl mx-auto px-4 md:px-6 py-12 grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-8">
        <div class="card-elev p-6">
            <h2 class="font-bold text-lg text-slate-900 mb-4">📑 Persyaratan</h2>
            <ul class="space-y-2">
                @foreach($service->requirements as $req)
                    <li class="flex items-start gap-3">
                        <span class="text-emerald-500 mt-1">✓</span>
                        <span class="text-slate-700">{{ $req }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card-elev p-6">
            <h2 class="font-bold text-lg text-slate-900 mb-4">🔄 Prosedur</h2>
            <ol class="space-y-3">
                @foreach($service->procedure as $i => $step)
                    <li class="flex gap-4">
                        <span class="flex-shrink-0 w-7 h-7 rounded-full brand-gradient text-white text-sm font-semibold flex items-center justify-center">{{ $i + 1 }}</span>
                        <span class="text-slate-700 pt-0.5">{{ $step }}</span>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="card-elev p-6">
            <h2 class="font-bold text-lg text-slate-900 mb-2">📄 Produk Layanan</h2>
            <p class="text-slate-700">{{ $service->output }}</p>
        </div>
    </div>

    <aside class="lg:col-span-1">
        <div class="card-elev p-6 sticky top-24">
            <h3 class="font-bold text-slate-900 mb-4">Ringkasan</h3>
            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Estimasi</span>
                    <span class="font-medium text-slate-900">⏱ {{ $service->sla_display }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Biaya</span>
                    <span class="font-medium text-emerald-700">💰 Gratis</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Diproses</span>
                    <span class="font-medium text-slate-900">{{ $service->bidang }}</span>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                <a href="{{ route('layanan.ajukan', $service->slug) }}" class="btn-primary w-full justify-center">
                    Ajukan Sekarang →
                </a>
                <a href="https://wa.me/6282269867911?text=Halo,%20saya%20ingin%20bertanya%20tentang%20{{ urlencode($service->name) }}" target="_blank" class="btn-outline w-full justify-center text-sm">
                    💬 Chat via WhatsApp
                </a>
            </div>

            <div class="mt-6 p-4 bg-blue-50 rounded-xl text-sm text-blue-900">
                💡 <strong>Tips:</strong> Pastikan semua berkas yang difoto/scan terbaca jelas dan tidak terpotong.
            </div>
        </div>
    </aside>
</section>

@endsection
