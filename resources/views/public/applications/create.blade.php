@extends('layouts.public')
@section('title', 'Ajukan: '.$service->name)
@section('content')

<section class="bg-slate-50 border-b border-slate-200">
    <div class="max-w-3xl mx-auto px-4 md:px-6 py-8">
        <a href="{{ route('layanan.show', $service->slug) }}" class="text-sm text-slate-500 hover:text-slate-700 mb-3 inline-block">‹ Kembali</a>
        <div class="text-xs font-mono text-slate-500">{{ $service->code }}</div>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $service->name }}</h1>
    </div>
</section>

<section class="max-w-3xl mx-auto px-4 md:px-6 py-10">
    @if ($errors->any())
        <div class="card-elev p-4 mb-6 border-l-4 border-rose-500 bg-rose-50">
            <div class="font-semibold text-rose-800 mb-1">Ada {{ $errors->count() }} kesalahan input:</div>
            <ul class="text-sm text-rose-700 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('layanan.ajukan.kirim', $service->slug) }}" enctype="multipart/form-data" class="space-y-6" x-data="{ anonymous: false }">
        @csrf

        {{-- Data Pemohon --}}
        <div class="card-elev p-6">
            <h2 class="font-bold text-slate-900 mb-1">① Data Pemohon</h2>
            <p class="text-sm text-slate-500 mb-5">Anda yang mengajukan layanan ini.</p>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap *</label>
                    <input name="applicant_name" type="text" required value="{{ old('applicant_name') }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">No HP (WhatsApp) *</label>
                    <input name="applicant_phone" type="tel" required placeholder="08xxxxxxxxxx" value="{{ old('applicant_phone') }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email (opsional)</label>
                    <input name="applicant_email" type="email" value="{{ old('applicant_email') }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
            </div>
        </div>

        {{-- Data Penerima Manfaat --}}
        <div class="card-elev p-6">
            <h2 class="font-bold text-slate-900 mb-1">② Penerima Manfaat</h2>
            <p class="text-sm text-slate-500 mb-5">Siapa yang akan menerima layanan ini.</p>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Hubungan dengan Anda</label>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach([
                            'diri_sendiri' => 'Diri sendiri',
                            'anggota_keluarga' => 'Anggota keluarga',
                            'kuasa' => 'Orang lain (kuasa)',
                        ] as $val => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="beneficiary_relation" value="{{ $val }}" {{ old('beneficiary_relation', 'diri_sendiri') === $val ? 'checked' : '' }} class="peer sr-only">
                                <div class="text-center px-3 py-3 border-2 border-slate-200 rounded-lg text-sm peer-checked:border-[color:var(--brand)] peer-checked:bg-blue-50 peer-checked:text-[color:var(--brand)] font-medium">{{ $label }}</div>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Penerima Manfaat *</label>
                    <input name="beneficiary_name" type="text" required value="{{ old('beneficiary_name') }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">NIK Penerima Manfaat</label>
                    <input name="beneficiary_nik" type="text" maxlength="16" inputmode="numeric" placeholder="16 digit" value="{{ old('beneficiary_nik') }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Keperluan / Tujuan (opsional)</label>
                    <textarea name="purpose" rows="2" placeholder="Mis. untuk pendaftaran sekolah" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">{{ old('purpose') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Berkas --}}
        <div class="card-elev p-6">
            <h2 class="font-bold text-slate-900 mb-1">③ Lengkapi Berkas</h2>
            <p class="text-sm text-slate-500 mb-5">Format: JPG/PNG/PDF · maks 2 MB per file. Pastikan terbaca jelas.</p>

            <div class="space-y-4">
                @foreach($service->requirements as $i => $req)
                    <div class="p-4 rounded-xl border-2 border-dashed border-slate-200 hover:border-[color:var(--brand)] transition">
                        <input type="hidden" name="doc_labels[]" value="{{ $req }}">
                        <input type="hidden" name="doc_types[]" value="dokumen_{{ $i }}">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">📎 {{ $req }}</span>
                            <input type="file" name="docs[]" accept=".jpg,.jpeg,.png,.pdf" {{ $i < 3 ? 'required' : '' }} class="mt-2 block w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-[color:var(--brand)] hover:file:bg-blue-100">
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Consent --}}
        <div class="card-elev p-6">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="consent" required class="mt-1 w-5 h-5 rounded text-[color:var(--brand)]">
                <span class="text-sm text-slate-700">
                    Saya menyatakan data yang saya isi adalah <strong>BENAR</strong> dan setuju data digunakan untuk keperluan layanan ini sesuai
                    <strong>UU Pelindungan Data Pribadi</strong>. Saya bersedia menerima konsekuensi hukum jika ditemukan ketidakbenaran.
                </span>
            </label>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('layanan.show', $service->slug) }}" class="text-slate-500 hover:text-slate-700">‹ Batal</a>
            <button type="submit" class="btn-primary">Kirim Pengajuan →</button>
        </div>
    </form>
</section>

@endsection
