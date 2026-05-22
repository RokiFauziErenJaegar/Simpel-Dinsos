@extends('layouts.public')
@section('title', 'Daftarkan Warga')
@section('content')

<section class="max-w-3xl mx-auto px-4 md:px-6 py-10">
    <a href="{{ route('pekon.dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-3 inline-block">‹ Kembali</a>
    <h1 class="text-2xl font-bold text-slate-900">Daftarkan Layanan untuk Warga</h1>
    <p class="text-slate-600 text-sm mt-1">Mode Operator Pekon — Anda mengajukan atas nama warga dengan e-sign PIN.</p>

    @if ($errors->any())
        <div class="mt-6 card-elev p-4 border-l-4 border-rose-500 bg-rose-50 text-rose-700 text-sm">
            @foreach($errors->all() as $err){{ $err }}<br>@endforeach
        </div>
    @endif

    <form method="post" action="{{ route('pekon.ajukan.kirim') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
        @csrf

        <div class="card-elev p-6">
            <h2 class="font-bold text-slate-900 mb-3">Pilih Layanan</h2>
            <select name="service_slug" required class="w-full px-3 py-2.5 rounded-lg border border-slate-200">
                <option value="">— pilih layanan —</option>
                @foreach($services as $s)
                    <option value="{{ $s->slug }}" {{ $service?->slug === $s->slug ? 'selected' : '' }}>
                        {{ $s->code }} · {{ $s->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="card-elev p-6 space-y-4">
            <h2 class="font-bold text-slate-900">Data Warga</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Warga *</label>
                    <input name="beneficiary_name" required value="{{ old('beneficiary_name') }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">NIK *</label>
                    <input name="beneficiary_nik" required maxlength="16" minlength="16" value="{{ old('beneficiary_nik') }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">No HP Warga *</label>
                    <input name="beneficiary_phone" required value="{{ old('beneficiary_phone') }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Keperluan</label>
                    <textarea name="purpose" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-slate-200">{{ old('purpose') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card-elev p-6">
            <h2 class="font-bold text-slate-900 mb-3">Unggah Berkas Warga</h2>
            <p class="text-xs text-slate-500 mb-3">Format: JPG/PNG/PDF · maks 2 MB</p>
            <input type="file" name="docs[]" multiple required accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-sm">
        </div>

        <div class="card-elev p-6 bg-amber-50 border-l-4 border-amber-500">
            <h2 class="font-bold text-amber-900">🔐 E-Sign Kepala Pekon</h2>
            <p class="text-sm text-amber-800 mt-1 mb-3">Masukkan PIN yang diberikan Kepala Pekon. Untuk demo, PIN default: <code class="bg-white px-1 rounded">123456</code></p>
            <input type="password" name="pin" required maxlength="10" placeholder="PIN 6 digit" class="w-full px-3 py-2.5 rounded-lg border border-amber-300">
        </div>

        <label class="flex items-start gap-3 card-elev p-4">
            <input type="checkbox" name="consent" required class="mt-1 w-5 h-5">
            <span class="text-sm text-slate-700">Saya konfirmasi data ini benar dan saya telah memperoleh persetujuan warga. Saya bertanggung jawab atas kebenaran data sesuai UU Pelindungan Data Pribadi.</span>
        </label>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Kirim Pengajuan →</button>
        </div>
    </form>
</section>

@endsection
