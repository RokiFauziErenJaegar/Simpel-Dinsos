@extends('layouts.public')
@section('title', 'Perbaiki Pengajuan '.$application->code)
@section('content')

<section class="bg-slate-50 border-b border-slate-200">
    <div class="max-w-3xl mx-auto px-4 md:px-6 py-8">
        <a href="{{ route('warga.dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-3 inline-block">‹ Kembali ke Akun</a>
        <div class="text-xs font-mono text-slate-500">{{ $application->code }}</div>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">Perbaiki & Kirim Ulang</h1>
        <p class="text-slate-600 text-sm mt-1">{{ $application->serviceType->name }}</p>
    </div>
</section>

<section class="max-w-3xl mx-auto px-4 md:px-6 py-10">
    {{-- Alasan pengembalian dari petugas --}}
    <div class="card-elev p-5 mb-6 border-l-4 border-amber-500 bg-amber-50">
        <div class="font-semibold text-amber-800 mb-1">📋 Pengajuan dikembalikan untuk diperbaiki</div>
        <p class="text-sm text-amber-700">{{ $returnReason ?? 'Mohon perbaiki berkas yang ditandai lalu kirim ulang.' }}</p>
    </div>

    @if ($errors->any())
        <div class="card-elev p-4 mb-6 border-l-4 border-rose-500 bg-rose-50">
            <div class="font-semibold text-rose-800 mb-1">Ada {{ $errors->count() }} kesalahan:</div>
            <ul class="text-sm text-rose-700 list-disc pl-5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('warga.application.fix.submit', $application->code) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Data Penerima --}}
        <div class="card-elev p-6">
            <h2 class="font-bold text-slate-900 mb-1">① Data Penerima Manfaat</h2>
            <p class="text-sm text-slate-500 mb-5">Perbaiki bila ada data yang keliru.</p>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Hubungan</label>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach(['diri_sendiri' => 'Diri sendiri', 'anggota_keluarga' => 'Anggota keluarga', 'kuasa' => 'Orang lain (kuasa)'] as $val => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="beneficiary_relation" value="{{ $val }}" {{ old('beneficiary_relation', $application->beneficiary_relation) === $val ? 'checked' : '' }} class="peer sr-only">
                                <div class="text-center px-3 py-3 border-2 border-slate-200 rounded-lg text-sm peer-checked:border-[color:var(--brand)] peer-checked:bg-blue-50 peer-checked:text-[color:var(--brand)] font-medium">{{ $label }}</div>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Penerima *</label>
                    <input name="beneficiary_name" type="text" required value="{{ old('beneficiary_name', $application->beneficiary_name) }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">NIK Penerima</label>
                    <input name="beneficiary_nik" type="text" maxlength="16" inputmode="numeric" placeholder="16 digit" value="{{ old('beneficiary_nik', $application->beneficiary_nik) }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Keperluan / Tujuan</label>
                    <textarea name="purpose" rows="2" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">{{ old('purpose', $application->purpose) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Berkas --}}
        <div class="card-elev p-6">
            <h2 class="font-bold text-slate-900 mb-1">② Berkas</h2>
            <p class="text-sm text-slate-500 mb-5">Berkas bertanda merah <strong>wajib</strong> diunggah ulang. Format JPG/PNG/PDF · maks 2 MB.</p>

            <div class="space-y-4">
                @foreach($application->documents as $doc)
                    @php $flagged = $doc->is_validated === false; @endphp
                    <div class="p-4 rounded-xl border-2 {{ $flagged ? 'border-rose-300 bg-rose-50' : 'border-slate-200' }}">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-slate-700">📎 {{ $doc->label }}</span>
                            @if($flagged)
                                <span class="text-xs font-semibold text-rose-600 bg-rose-100 px-2 py-0.5 rounded-full flex-shrink-0">Perlu diperbaiki</span>
                            @else
                                <span class="text-xs text-slate-400 truncate">{{ $doc->original_name }}</span>
                            @endif
                        </div>
                        @if($flagged && $doc->notes)
                            <p class="text-xs text-rose-600 mt-1">Catatan petugas: {{ $doc->notes }}</p>
                        @endif
                        <input type="file" name="replace_docs[{{ $doc->id }}]" accept=".jpg,.jpeg,.png,.pdf" {{ $flagged ? 'required' : '' }} class="mt-2 block w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-[color:var(--brand)] hover:file:bg-blue-100">
                        @if(! $flagged)
                            <p class="text-xs text-slate-400 mt-1">Kosongkan bila tidak perlu diganti.</p>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Tambah berkas baru --}}
            <div class="mt-5 pt-5 border-t border-slate-100">
                <div class="text-sm font-medium text-slate-700 mb-2">Tambah berkas (opsional)</div>
                <div class="grid md:grid-cols-2 gap-2">
                    <input type="text" name="new_doc_labels[]" placeholder="Nama berkas (mis. Surat domisili)" class="px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                    <input type="file" name="new_docs[]" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-[color:var(--brand)] hover:file:bg-blue-100">
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('warga.dashboard') }}" class="text-slate-500 hover:text-slate-700">‹ Batal</a>
            <button type="submit" class="btn-primary">Kirim Ulang →</button>
        </div>
    </form>
</section>

@endsection
