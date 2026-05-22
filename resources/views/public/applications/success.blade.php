@extends('layouts.public')
@section('title', 'Pengajuan Berhasil')
@section('content')

<section class="max-w-2xl mx-auto px-4 md:px-6 py-16">
    <div class="text-center">
        <div class="w-20 h-20 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-4xl">✓</div>
        <h1 class="mt-6 text-3xl font-bold text-slate-900">Pengajuan Berhasil Dikirim!</h1>
        <p class="mt-2 text-slate-600">Terima kasih. Tim Dinas Sosial akan segera memproses pengajuan Anda.</p>
    </div>

    <div class="mt-10 card-elev p-8 text-center">
        <div class="text-xs uppercase tracking-wide text-slate-500">Nomor Antrian Anda</div>
        <div class="mt-2 text-6xl font-extrabold text-[color:var(--brand)]">{{ $application->queueTicket?->ticket_number ?? '—' }}</div>

        <div class="mt-6 grid grid-cols-2 gap-4 text-sm text-left">
            <div class="bg-slate-50 rounded-lg p-3">
                <div class="text-slate-500 text-xs">Kode Pengajuan</div>
                <div class="font-mono font-semibold mt-1">{{ $application->code }}</div>
            </div>
            <div class="bg-slate-50 rounded-lg p-3">
                <div class="text-slate-500 text-xs">Layanan</div>
                <div class="font-semibold mt-1">{{ \Illuminate\Support\Str::limit($application->serviceType->name, 40) }}</div>
            </div>
            <div class="bg-slate-50 rounded-lg p-3">
                <div class="text-slate-500 text-xs">Penerima</div>
                <div class="font-semibold mt-1">{{ $application->beneficiary_name }}</div>
            </div>
            <div class="bg-slate-50 rounded-lg p-3">
                <div class="text-slate-500 text-xs">Estimasi Selesai</div>
                <div class="font-semibold mt-1">{{ $application->sla_due_at?->translatedFormat('d M, H:i') ?? '—' }}</div>
            </div>
        </div>

        <div class="mt-6 p-4 bg-blue-50 rounded-xl text-sm text-blue-900">
            📲 Tiket & update status akan dikirim ke nomor WhatsApp Anda. Simpan kode pengajuan untuk pengecekan kapan saja.
        </div>

        <div class="mt-6 flex flex-wrap gap-3 justify-center">
            <a href="{{ route('cek-status.index', ['code' => $application->code]) }}" class="btn-primary">Pantau Status Real-time</a>
            <a href="{{ route('home') }}" class="btn-outline">Kembali ke Beranda</a>
        </div>

        <div class="mt-4 text-xs text-slate-400">
            Notifikasi WA simulasi tersimpan di <code>storage/app/private/outbox/{{ now()->format('Y-m-d') }}.log</code>
        </div>
    </div>
</section>

@endsection
