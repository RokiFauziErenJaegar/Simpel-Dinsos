@extends('layouts.public')
@section('title', 'Mode Offline')
@section('content')

<section class="max-w-2xl mx-auto px-4 md:px-6 py-20 text-center">
    <div class="w-20 h-20 mx-auto rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-4xl">📡</div>
    <h1 class="mt-6 text-2xl font-bold text-slate-900">Anda Sedang Offline</h1>
    <p class="mt-2 text-slate-600">Koneksi internet terputus. Beberapa fitur masih bisa diakses dari cache:</p>

    <div class="mt-8 grid grid-cols-2 gap-3">
        <a href="/" class="card-elev p-4 hover:-translate-y-0.5 transition">
            <div class="text-3xl mb-1">🏠</div>
            <div class="font-semibold text-sm">Beranda</div>
        </a>
        <a href="/layanan" class="card-elev p-4 hover:-translate-y-0.5 transition">
            <div class="text-3xl mb-1">📋</div>
            <div class="font-semibold text-sm">Katalog Layanan</div>
        </a>
        <a href="/cek-status" class="card-elev p-4 hover:-translate-y-0.5 transition">
            <div class="text-3xl mb-1">🔍</div>
            <div class="font-semibold text-sm">Cek Status</div>
        </a>
        <a href="/pengaduan" class="card-elev p-4 hover:-translate-y-0.5 transition">
            <div class="text-3xl mb-1">💬</div>
            <div class="font-semibold text-sm">Pengaduan</div>
        </a>
    </div>

    <div class="mt-8 p-4 bg-blue-50 text-blue-900 text-sm rounded-xl">
        💡 Cek koneksi WiFi/data, lalu refresh halaman ini. Pengajuan baru memerlukan koneksi online.
    </div>

    <button onclick="window.location.reload()" class="btn-primary mt-6">↻ Coba Lagi</button>
</section>

@endsection
