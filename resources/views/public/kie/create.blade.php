@extends('layouts.public')
@section('title', 'Konsultasi Warga (KIE)')
@section('content')

<section class="bg-slate-50 border-b border-slate-200">
    <div class="max-w-3xl mx-auto px-4 md:px-6 py-8">
        <a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-3 inline-block">‹ Kembali</a>
        <div class="text-xs font-mono text-slate-500">KIE</div>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">Konsultasi Warga (KIE)</h1>
        <p class="text-sm text-slate-500 mt-1">Komunikasi, Informasi &amp; Edukasi. Isi data diri Anda sebelum berkonsultasi dengan petugas. Gratis.</p>
    </div>
</section>

<section class="max-w-3xl mx-auto px-4 md:px-6 py-10">

    {{-- Info minimalis: capaian KIE hari ini --}}
    <div class="card-elev p-4 mb-6 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl brand-gradient flex items-center justify-center text-white text-xl font-bold flex-shrink-0">{{ $todayCount }}</div>
        <div>
            <div class="font-semibold text-slate-900 leading-tight">Konsultasi hari ini</div>
            <div class="text-sm text-slate-500">{{ now()->translatedFormat('l, d F Y') }} · terdaftar {{ $todayCount }} konsultasi</div>
        </div>
    </div>

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

    <form method="post" action="{{ route('kie.kirim') }}" class="space-y-6"
          x-data="{ location: '{{ old('location', $presetLocation?->value ?? '') }}' }">
        @csrf
        <input type="hidden" name="form_nonce" value="{{ $formNonce }}">

        <div class="card-elev p-6">
            <h2 class="font-bold text-slate-900 mb-1">Data Diri</h2>
            <p class="text-sm text-slate-500 mb-5">Nomor WhatsApp wajib diisi agar Anda menerima konfirmasi.</p>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap *</label>
                    <input name="name" type="text" required value="{{ old('name') }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">No HP (WhatsApp) *</label>
                    <input name="phone" type="tel" required placeholder="08xxxxxxxxxx" value="{{ old('phone') }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">NIK (opsional)</label>
                    <input name="nik" type="text" maxlength="16" inputmode="numeric" placeholder="16 digit" value="{{ old('nik') }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Alamat (opsional)</label>
                    <input name="address" type="text" value="{{ old('address') }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
                </div>
            </div>
        </div>

        <div class="card-elev p-6">
            <h2 class="font-bold text-slate-900 mb-1">Keperluan Konsultasi</h2>
            <p class="text-sm text-slate-500 mb-5">Ceritakan singkat hal yang ingin dikonsultasikan.</p>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Topik (opsional)</label>
                    <input name="topic" type="text" list="kie-topics" value="{{ old('topic') }}" placeholder="Mis. Bantuan sosial, PPKS, DTSEN" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
                    <datalist id="kie-topics">
                        <option value="Bantuan Sosial"></option>
                        <option value="PPKS / Rehabilitasi Sosial"></option>
                        <option value="DTSEN / Data Kesejahteraan"></option>
                        <option value="Disabilitas"></option>
                        <option value="Lanjut Usia"></option>
                        <option value="Anak &amp; Keluarga"></option>
                        <option value="Lembaga/Yayasan Sosial"></option>
                        <option value="Lainnya"></option>
                    </datalist>
                </div>
                @if(! $presetLocation)
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Lokasi Layanan (opsional)</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(\App\Enums\ServiceLocation::cases() as $loc)
                                <label class="cursor-pointer">
                                    <input type="radio" name="location" value="{{ $loc->value }}" x-model="location" class="peer sr-only">
                                    <div class="text-center px-3 py-2.5 border-2 border-slate-200 rounded-lg text-sm peer-checked:border-[color:var(--brand)] peer-checked:bg-blue-50 peer-checked:text-[color:var(--brand)] font-medium">{{ $loc->shortLabel() }}</div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @else
                    <input type="hidden" name="location" value="{{ $presetLocation->value }}">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Lokasi Layanan</label>
                        <div class="px-3 py-2.5 rounded-lg bg-slate-50 border border-slate-200 text-sm text-slate-700">{{ $presetLocation->label() }}</div>
                    </div>
                @endif
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Uraian (opsional)</label>
                    <textarea name="description" rows="3" placeholder="Tuliskan hal yang ingin Anda konsultasikan…" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card-elev p-6">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="consent" required class="mt-1 w-5 h-5 rounded text-[color:var(--brand)]">
                <span class="text-sm text-slate-700">
                    Saya setuju data ini digunakan untuk keperluan pencatatan layanan konsultasi sesuai <strong>UU Pelindungan Data Pribadi</strong>.
                </span>
            </label>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('home') }}" class="text-slate-500 hover:text-slate-700">‹ Batal</a>
            <button type="submit" class="btn-primary">Daftar Konsultasi →</button>
        </div>
    </form>
</section>

<script>
(function () {
    const form = document.querySelector('form[action="{{ route('kie.kirim') }}"]');
    if (!form) return;
    let submitted = false;
    form.addEventListener('submit', () => {
        if (submitted) return;
        submitted = true;
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.style.cursor = 'not-allowed';
            btn.textContent = 'Mengirim… mohon tunggu';
        }
    });
})();
</script>

@endsection
