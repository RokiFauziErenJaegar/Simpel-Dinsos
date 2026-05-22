@extends('layouts.public')
@section('title', 'Cek Status')
@section('content')

<section class="max-w-3xl mx-auto px-4 md:px-6 py-12">
    <h1 class="text-3xl font-bold text-slate-900">Cek Status Pengajuan</h1>
    <p class="text-slate-600 mt-1">Masukkan kode pengajuan Anda untuk melihat status real-time.</p>

    <form method="get" class="mt-6 card-elev p-6 flex gap-3">
        <input type="text" name="code" value="{{ $code }}" placeholder="Contoh: SURAT-2026-0001" class="flex-1 px-4 py-3 rounded-xl border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
        <button type="submit" class="btn-primary">Cek</button>
    </form>

    @if ($code && ! $application)
        <div class="mt-6 card-elev p-6 border-l-4 border-amber-500 bg-amber-50">
            <div class="font-semibold text-amber-800">Kode tidak ditemukan</div>
            <p class="text-sm text-amber-700 mt-1">Pastikan kode pengajuan Anda benar (huruf besar, dengan tanda hubung).</p>
        </div>
    @endif

    @if ($application)
        <div class="mt-8 card-elev p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <div class="text-xs font-mono text-slate-500">{{ $application->code }}</div>
                    <h2 class="font-bold text-slate-900 mt-1">{{ $application->serviceType->name }}</h2>
                    <div class="text-sm text-slate-500 mt-1">Untuk: <strong>{{ $application->beneficiary_name }}</strong></div>
                </div>
                @php $color = $application->status->color(); @endphp
                <span class="px-3 py-1 rounded-full text-xs font-semibold
                    @if($color === 'success') bg-emerald-100 text-emerald-700
                    @elseif($color === 'warning') bg-amber-100 text-amber-700
                    @elseif($color === 'danger') bg-rose-100 text-rose-700
                    @elseif($color === 'info') bg-blue-100 text-blue-700
                    @else bg-slate-100 text-slate-700 @endif">
                    {{ $application->status->label() }}
                </span>
            </div>

            <div class="mt-6 p-5 rounded-xl
                @if($application->isOverdue()) bg-rose-50 border border-rose-200
                @elseif($application->status->isFinal() && $application->status->value === 'completed') bg-emerald-50 border border-emerald-200
                @else bg-blue-50 border border-blue-200 @endif">
                <div class="text-sm">
                    @if($application->status->value === 'completed')
                        ✅ <strong>Pengajuan telah selesai diproses</strong> pada {{ $application->completed_at?->translatedFormat('d M Y H:i') }}.
                        @if($application->outputDocument)
                            <a href="{{ route('document.verify', ['token' => $application->outputDocument->verification_token]) }}" class="block mt-3 btn-primary text-sm">Unduh Surat</a>
                        @endif
                    @elseif($application->isOverdue())
                        ⚠ <strong>Pengajuan ini melebihi SLA standar.</strong> Tim Dinas Sosial sedang menyelesaikan secepat mungkin.
                    @else
                        🕐 Estimasi selesai: <strong>{{ $application->sla_due_at?->translatedFormat('d M Y, H:i') }}</strong>
                        @if($application->queueTicket && $application->queueTicket->status === 'waiting')
                            <div class="mt-2 text-xs">Nomor antrian: <strong>{{ $application->queueTicket->ticket_number }}</strong></div>
                        @endif
                    @endif
                </div>
            </div>

            <h3 class="font-semibold text-slate-900 mt-8 mb-4">Timeline</h3>
            <div class="relative pl-6 border-l-2 border-slate-200 space-y-5">
                @foreach($application->logs as $log)
                    <div class="relative">
                        <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-[color:var(--brand)] border-4 border-white shadow"></div>
                        <div class="text-xs text-slate-500">{{ $log->created_at->translatedFormat('d M Y · H:i') }}</div>
                        <div class="font-medium text-slate-900 mt-0.5">
                            @switch($log->action)
                                @case('created') Pengajuan dikirim oleh pemohon @break
                                @case('verified') Berkas diverifikasi @break
                                @case('disposed') Disposisi ke {{ $log->user?->name }} @break
                                @case('field_verification') Verifikasi lapangan @break
                                @case('signed') Ditandatangani Kepala Dinas @break
                                @case('completed') Pengajuan selesai @break
                                @case('returned') Dikembalikan ke pemohon @break
                                @case('rejected') Ditolak @break
                                @default {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                            @endswitch
                        </div>
                        @if ($log->notes)
                            <div class="text-sm text-slate-600 mt-1">{{ $log->notes }}</div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-8 p-4 bg-slate-50 rounded-xl text-sm text-slate-700">
                💬 Butuh bantuan? Hubungi <a href="https://wa.me/6282269867911" class="text-[color:var(--brand)] font-semibold">0822-6986-7911</a> dengan menyebut kode pengajuan Anda.
            </div>
        </div>
    @endif
</section>

@endsection
