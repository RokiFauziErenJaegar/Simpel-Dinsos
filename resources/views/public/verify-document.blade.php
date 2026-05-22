@extends('layouts.public')
@section('title', 'Verifikasi Dokumen')
@section('content')

<section class="max-w-2xl mx-auto px-4 md:px-6 py-16">
    <div class="text-center mb-8">
        <div class="w-20 h-20 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-4xl">✓</div>
        <h1 class="mt-6 text-2xl font-bold text-slate-900">Dokumen Resmi Terverifikasi</h1>
        <p class="mt-2 text-slate-600">Dokumen ini benar diterbitkan oleh Dinas Sosial Kabupaten Pringsewu.</p>
    </div>

    <div class="card-elev p-6">
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b border-slate-100 pb-2">
                <span class="text-slate-500">Nomor Dokumen</span>
                <span class="font-mono font-semibold">{{ $document->document_number }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
                <span class="text-slate-500">Jenis Layanan</span>
                <span class="font-semibold text-right">{{ $document->application->serviceType->name }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
                <span class="text-slate-500">Penerima Manfaat</span>
                <span class="font-semibold">{{ $document->application->beneficiary_name }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
                <span class="text-slate-500">Ditandatangani Oleh</span>
                <span class="font-semibold">{{ $document->signedBy?->name ?? 'Kepala Dinas Sosial' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Tanggal Tanda Tangan</span>
                <span class="font-semibold">{{ $document->signed_at?->translatedFormat('d M Y H:i') ?? '—' }}</span>
            </div>
        </div>

        @if($document->file_path)
            <div class="mt-6 text-center">
                <a href="{{ asset('storage/'.$document->file_path) }}" target="_blank" class="btn-primary">📄 Buka / Unduh Dokumen</a>
            </div>
        @endif
    </div>
</section>

@endsection
