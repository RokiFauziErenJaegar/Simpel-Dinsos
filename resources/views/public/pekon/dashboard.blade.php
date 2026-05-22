@extends('layouts.public')
@section('title', 'Operator Pekon')
@section('content')

<section class="max-w-6xl mx-auto px-4 md:px-6 py-10">
    <div class="flex items-start justify-between mb-8">
        <div>
            <div class="text-xs font-semibold text-[color:var(--brand)] uppercase tracking-wide">Mode Operator Pekon</div>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $operator->pekon?->name ?? 'Pekon' }}, {{ $operator->kecamatan?->name ?? '' }}</h1>
            <p class="text-slate-600 text-sm mt-1">{{ $operator->name }}</p>
        </div>
        <form method="post" action="{{ route('warga.logout') }}">@csrf<button class="btn-outline text-sm">Keluar</button></form>
    </div>

    @if(session('success'))
        <div class="mb-6 card-elev p-4 border-l-4 border-emerald-500 bg-emerald-50 text-emerald-800 text-sm">✓ {{ session('success') }}</div>
    @endif

    <div class="grid md:grid-cols-3 gap-4 mb-8">
        <div class="card-elev p-5">
            <div class="text-3xl font-bold text-[color:var(--brand)]">{{ $stats['submitted'] }}</div>
            <div class="text-sm text-slate-500 mt-1">Total Pengajuan</div>
        </div>
        <div class="card-elev p-5">
            <div class="text-3xl font-bold text-amber-500">{{ $stats['in_progress'] }}</div>
            <div class="text-sm text-slate-500 mt-1">Sedang Proses</div>
        </div>
        <div class="card-elev p-5">
            <div class="text-3xl font-bold text-emerald-600">{{ $stats['completed'] }}</div>
            <div class="text-sm text-slate-500 mt-1">Selesai</div>
        </div>
    </div>

    <h2 class="font-bold text-slate-900 mb-3">Daftarkan Warga ke Layanan</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-10">
        @foreach($services->take(8) as $s)
            <a href="{{ route('pekon.ajukan', ['service' => $s->slug]) }}" class="card-elev p-4 hover:-translate-y-0.5 transition">
                <div class="text-xs font-mono text-slate-400">{{ $s->code }}</div>
                <div class="font-semibold text-sm text-slate-900 mt-1 leading-tight">{{ \Illuminate\Support\Str::limit($s->name, 50) }}</div>
                <div class="text-xs text-slate-500 mt-2">⏱ {{ $s->sla_display }}</div>
            </a>
        @endforeach
    </div>
    <a href="{{ route('pekon.ajukan') }}" class="btn-primary mb-10 inline-flex">+ Daftarkan Warga (semua layanan)</a>

    <h2 class="font-bold text-slate-900 mb-3 mt-10">Pengajuan Warga {{ $operator->pekon?->name }}</h2>
    @if($recent->isEmpty())
        <div class="card-elev p-8 text-center text-slate-500 text-sm">
            Belum ada pengajuan warga dari pekon ini.
        </div>
    @else
        <div class="space-y-3">
            @foreach($recent as $a)
                <a href="{{ route('cek-status.index', ['code' => $a->code]) }}" class="card-elev p-4 flex items-center justify-between hover:-translate-y-0.5 transition">
                    <div>
                        <div class="text-xs font-mono text-slate-400">{{ $a->code }}</div>
                        <div class="font-semibold text-slate-900 mt-0.5">{{ $a->beneficiary_name }} — {{ \Illuminate\Support\Str::limit($a->serviceType->name, 50) }}</div>
                        <div class="text-xs text-slate-500 mt-1">{{ $a->submitted_at?->diffForHumans() }}</div>
                    </div>
                    @php $color = $a->status->color(); @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        @if($color === 'success') bg-emerald-100 text-emerald-700
                        @elseif($color === 'warning') bg-amber-100 text-amber-700
                        @elseif($color === 'danger') bg-rose-100 text-rose-700
                        @else bg-slate-100 text-slate-700 @endif">{{ $a->status->label() }}</span>
                </a>
            @endforeach
        </div>
    @endif
</section>

@endsection
