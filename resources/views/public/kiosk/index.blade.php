<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kiosk Self-Service · Dinsos Pringsewu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%); min-height: 100vh; }
        .kiosk-card { background: white; border-radius: 1.5rem; box-shadow: 0 12px 40px rgba(0,0,0,0.08); }
        .big-btn { font-size: 1.5rem; padding: 1.5rem 2rem; border-radius: 1rem; transition: all 0.2s; }
        .big-btn:active { transform: scale(0.97); }
    </style>
</head>
<body class="flex flex-col items-center pt-10 pb-10">

<header class="text-center mb-6">
    <h1 class="text-4xl font-extrabold text-slate-900">DINSOS PRINGSEWU</h1>
    <p class="text-slate-600 mt-1">Kiosk Pelayanan Lobi · Sentuh Layar untuk Memulai</p>
</header>

<div class="kiosk-card p-10 w-full max-w-2xl" x-data="kiosk()">
    <template x-if="step === 'menu'">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 mb-6 text-center">Apa keperluan Anda?</h2>
            <div class="grid grid-cols-1 gap-4">
                <button @click="step = 'name'" class="big-btn bg-blue-600 hover:bg-blue-700 text-white font-semibold text-left">
                    🪪  Ambil Nomor Antrian
                    <div class="text-sm font-normal opacity-80 mt-1">Untuk konsultasi langsung dengan petugas</div>
                </button>
                <a href="{{ route('layanan.index') }}" class="big-btn bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-left block">
                    📋  Ajukan Layanan Online
                    <div class="text-sm font-normal opacity-80 mt-1">Daftar mandiri lewat HP / komputer</div>
                </a>
                <a href="{{ route('cek-status.index') }}" class="big-btn bg-slate-700 hover:bg-slate-800 text-white font-semibold text-left block">
                    🔍  Cek Status Pengajuan
                </a>
                <a href="{{ route('pengaduan.create') }}" class="big-btn bg-amber-500 hover:bg-amber-600 text-white font-semibold text-left block">
                    💬  Sampaikan Pengaduan / Saran
                </a>
            </div>
        </div>
    </template>

    <template x-if="step === 'name'">
        <form method="post" action="{{ route('kiosk.ticket') }}">
            @csrf
            <h2 class="text-2xl font-bold text-slate-900 mb-4 text-center">Ambil Nomor Antrian</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nama Lengkap</label>
                    <input name="walk_in_name" required class="w-full text-2xl px-4 py-4 border-2 border-slate-200 rounded-xl">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Layanan yang Diperlukan</label>
                    <select name="service_type_id" required class="w-full text-lg px-4 py-3 border-2 border-slate-200 rounded-xl">
                        <option value="">— pilih —</option>
                        @foreach($services as $s)
                            <option value="{{ $s->id }}">{{ $s->code }} · {{ \Illuminate\Support\Str::limit($s->name, 50) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Apakah Anda lansia / disabilitas / ibu hamil?</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="priority" value="normal" checked class="peer sr-only">
                            <div class="text-center px-4 py-3 border-2 border-slate-200 rounded-xl peer-checked:border-blue-600 peer-checked:bg-blue-50 font-medium">Tidak</div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="priority" value="prioritas" class="peer sr-only">
                            <div class="text-center px-4 py-3 border-2 border-slate-200 rounded-xl peer-checked:border-amber-500 peer-checked:bg-amber-50 font-medium">Ya, Prioritas</div>
                        </label>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" @click="step = 'menu'" class="flex-1 big-btn bg-slate-200 text-slate-700 font-semibold">‹ Batal</button>
                    <button type="submit" class="flex-1 big-btn bg-blue-600 text-white font-semibold">Cetak Tiket →</button>
                </div>
            </div>
        </form>
    </template>
</div>

<footer class="mt-10 text-slate-500 text-sm">
    Bantuan? Hubungi petugas loket · WA 0822-6986-7911
</footer>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function kiosk() { return { step: 'menu' } }
</script>
</body>
</html>
