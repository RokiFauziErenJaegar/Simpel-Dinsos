@extends('layouts.public')
@section('title', 'Akun Saya')
@section('content')

<section class="max-w-5xl mx-auto px-4 md:px-6 py-10">
    <div class="flex items-start justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Halo, {{ $user->name }} 👋</h1>
            <p class="text-slate-600 text-sm mt-1">{{ $user->phone }}</p>
        </div>
        <form method="post" action="{{ route('warga.logout') }}">
            @csrf
            <button class="btn-outline text-sm">Keluar</button>
        </form>
    </div>

    <div class="grid md:grid-cols-3 gap-4 mb-8">
        <div class="card-elev p-5">
            <div class="text-3xl font-bold text-[color:var(--brand)]">{{ $applications->count() }}</div>
            <div class="text-sm text-slate-500 mt-1">Total Pengajuan</div>
        </div>
        <div class="card-elev p-5">
            <div class="text-3xl font-bold text-amber-500">{{ $applications->filter(fn($a) => ! in_array($a->status?->value, ['completed', 'rejected']))->count() }}</div>
            <div class="text-sm text-slate-500 mt-1">Sedang Proses</div>
        </div>
        <div class="card-elev p-5">
            <div class="text-3xl font-bold text-emerald-600">{{ $applications->where('status.value', 'completed')->count() }}</div>
            <div class="text-sm text-slate-500 mt-1">Selesai</div>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <h2 class="font-bold text-slate-900">Pengajuan Saya</h2>
        <a href="{{ route('layanan.index') }}" class="btn-primary text-sm">+ Ajukan Layanan Baru</a>
    </div>

    @if($applications->isEmpty())
        <div class="card-elev p-10 text-center text-slate-500">
            <div class="text-4xl mb-3">📋</div>
            Belum ada pengajuan. Klik tombol di atas untuk mengajukan layanan pertama Anda.
        </div>
    @else
        <div class="space-y-3">
            @foreach($applications as $app)
                <a href="{{ route('cek-status.index', ['code' => $app->code]) }}" class="card-elev p-4 flex items-center justify-between hover:-translate-y-0.5 transition">
                    <div>
                        <div class="text-xs font-mono text-slate-400">{{ $app->code }}</div>
                        <div class="font-semibold text-slate-900 mt-0.5">{{ \Illuminate\Support\Str::limit($app->serviceType->name, 60) }}</div>
                        <div class="text-xs text-slate-500 mt-1">{{ $app->submitted_at?->translatedFormat('d M Y H:i') }}</div>
                    </div>
                    @php $color = $app->status->color(); @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-semibold flex-shrink-0
                        @if($color === 'success') bg-emerald-100 text-emerald-700
                        @elseif($color === 'warning') bg-amber-100 text-amber-700
                        @elseif($color === 'danger') bg-rose-100 text-rose-700
                        @else bg-slate-100 text-slate-700 @endif">
                        {{ $app->status->label() }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</section>

@endsection
