@extends('layouts.public')
@section('title', 'Hak Atas Data Saya')
@section('content')

<section class="max-w-3xl mx-auto px-4 md:px-6 py-10">
    <a href="{{ route('warga.dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-3 inline-block">‹ Kembali</a>
    <h1 class="text-2xl font-bold text-slate-900">Hak Atas Data Pribadi Saya</h1>
    <p class="text-slate-600 text-sm mt-1">Sesuai <strong>UU 27/2022 tentang Pelindungan Data Pribadi</strong>, Anda berhak mengakses, mengekspor, dan menghapus data pribadi yang kami simpan.</p>

    {{-- Hak Akses --}}
    <div class="mt-6 card-elev p-6">
        <div class="flex items-start gap-4">
            <div class="text-3xl">📋</div>
            <div class="flex-1">
                <h2 class="font-bold text-slate-900">Hak Akses (Pasal 5)</h2>
                <p class="text-sm text-slate-600 mt-1">Lihat seluruh pengajuan & data Anda di dashboard.</p>
                <a href="{{ route('warga.dashboard') }}" class="btn-outline text-sm mt-3 inline-flex">Buka Dashboard Saya</a>
            </div>
        </div>
    </div>

    {{-- Hak Portabilitas --}}
    <div class="mt-4 card-elev p-6">
        <div class="flex items-start gap-4">
            <div class="text-3xl">📦</div>
            <div class="flex-1">
                <h2 class="font-bold text-slate-900">Hak Portabilitas Data (Pasal 13)</h2>
                <p class="text-sm text-slate-600 mt-1">Unduh seluruh data pribadi Anda dalam format JSON yang dapat dibaca mesin & dipindahkan ke sistem lain.</p>
                <form method="get" action="{{ route('warga.data.export') }}" class="mt-3">
                    <button class="btn-primary text-sm">Unduh Data Saya (JSON) ↓</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Hak Rektifikasi --}}
    <div class="mt-4 card-elev p-6">
        <div class="flex items-start gap-4">
            <div class="text-3xl">✏️</div>
            <div class="flex-1">
                <h2 class="font-bold text-slate-900">Hak Rektifikasi (Pasal 9)</h2>
                <p class="text-sm text-slate-600 mt-1">Untuk mengoreksi data salah, hubungi WA <strong>0822-6986-7911</strong> dengan menyertakan kode pengajuan & koreksi yang diperlukan.</p>
            </div>
        </div>
    </div>

    {{-- Hak Menghapus --}}
    <div class="mt-4 card-elev p-6 border-l-4 border-rose-500" x-data="{ open: false }">
        <div class="flex items-start gap-4">
            <div class="text-3xl">🗑️</div>
            <div class="flex-1">
                <h2 class="font-bold text-rose-700">Hak Menghapus Data (Pasal 8) & Menarik Persetujuan</h2>
                <p class="text-sm text-slate-600 mt-1">Permintaan ini akan menghapus seluruh data pribadi Anda dalam 30 hari. <strong>Aksi tidak dapat dibatalkan setelah 30 hari berlalu.</strong></p>

                <button @click="open = true" x-show="!open" class="text-sm text-rose-700 font-semibold mt-3 hover:underline">Saya ingin menghapus data saya →</button>

                <form x-show="open" method="post" action="{{ route('warga.data.delete') }}" class="mt-4 space-y-3" x-transition>
                    @csrf
                    <div class="p-3 bg-rose-50 text-rose-800 text-sm rounded-lg">
                        ⚠ Tindakan ini akan menyebabkan: data profil, semua pengajuan, dan berkas Anda di-soft-delete. Setelah 30 hari, sistem akan menghapus permanen sesuai SOP retensi UU PDP.
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Ketik <code class="bg-slate-100 px-1 rounded">HAPUS DATA SAYA</code> untuk mengkonfirmasi *</label>
                        <input name="confirm" required class="w-full px-3 py-2 rounded-lg border border-rose-300 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Alasan (opsional)</label>
                        <textarea name="reason" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-200"></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="open = false" class="btn-outline text-sm">Batal</button>
                        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl font-semibold text-sm">Konfirmasi Hapus Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-6 p-4 bg-blue-50 text-blue-900 text-sm rounded-lg">
        💡 Setiap akses ke data pribadi Anda oleh petugas Dinsos otomatis tercatat dalam <strong>Data Access Log</strong> dan dapat Anda mintakan rekapnya kepada DPO Dinsos.
    </div>
</section>

@endsection
