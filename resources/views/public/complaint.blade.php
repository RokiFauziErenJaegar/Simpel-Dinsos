@extends('layouts.public')
@section('title', 'Pengaduan Masyarakat')
@section('content')

<section class="max-w-3xl mx-auto px-4 md:px-6 py-12">
    <h1 class="text-3xl font-bold text-slate-900">Sampaikan Pengaduan / Saran</h1>
    <p class="text-slate-600 mt-1">Kanal pengaduan resmi Dinas Sosial Kabupaten Pringsewu. Anda dapat melaporkan secara anonim.</p>

    @if (session('success'))
        <div class="mt-6 card-elev p-4 border-l-4 border-emerald-500 bg-emerald-50 text-emerald-800 text-sm">
            ✓ {{ session('success') }}
        </div>
    @endif

    <form method="post" action="{{ route('pengaduan.store') }}" class="mt-6 card-elev p-6 space-y-5" x-data="{ anon: false }">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Subjek *</label>
            <input name="subject" required maxlength="200" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Isi Pengaduan *</label>
            <textarea name="content" required rows="5" maxlength="5000" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] focus:ring-2 focus:ring-blue-100 outline-none"></textarea>
        </div>

        <label class="flex items-center gap-3">
            <input type="checkbox" name="is_anonymous" value="1" x-model="anon" class="w-5 h-5 rounded">
            <span class="text-sm text-slate-700">Laporkan secara <strong>anonim</strong></span>
        </label>

        <div x-show="!anon" x-transition class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Anda</label>
                <input name="reporter_name" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Kontak (HP/Email)</label>
                <input name="reporter_contact" class="w-full px-3 py-2.5 rounded-lg border border-slate-200 focus:border-[color:var(--brand)] outline-none">
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Kirim Pengaduan →</button>
        </div>
    </form>

    <div class="mt-8 grid md:grid-cols-3 gap-4 text-sm">
        <div class="card-elev p-4">
            <div class="text-2xl mb-1">📞</div>
            <div class="font-semibold">Call Center</div>
            <div class="text-slate-500">0822-6986-7911</div>
        </div>
        <div class="card-elev p-4">
            <div class="text-2xl mb-1">✉️</div>
            <div class="font-semibold">Email</div>
            <div class="text-slate-500 break-all">pringsewudinsos@gmail.com</div>
        </div>
        <div class="card-elev p-4">
            <div class="text-2xl mb-1">🌐</div>
            <div class="font-semibold">Lapor.go.id</div>
            <div class="text-slate-500">Anonim & terintegrasi nasional</div>
        </div>
    </div>
</section>

@endsection
