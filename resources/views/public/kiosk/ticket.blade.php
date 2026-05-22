<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Antrian {{ $ticket->ticket_number }}</title>
    @vite('resources/css/app.css')
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex flex-col items-center justify-center p-6">

<div class="bg-white rounded-2xl shadow-xl p-8 max-w-md text-center border-2 border-dashed border-slate-300">
    <div class="text-xs uppercase tracking-widest text-slate-500">Dinas Sosial Pringsewu</div>
    <div class="text-xs text-slate-400 mt-1">{{ now()->translatedFormat('l, d F Y · H:i') }}</div>

    <div class="my-8">
        <div class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Nomor Antrian</div>
        <div class="text-8xl font-extrabold text-blue-600 mt-2 tracking-tight">{{ $ticket->ticket_number }}</div>
    </div>

    <div class="space-y-2 text-sm text-slate-700">
        <div><strong>Nama:</strong> {{ $ticket->walk_in_name }}</div>
        @if($ticket->priority === 'prioritas')
            <div class="inline-flex items-center gap-1 px-3 py-1 bg-amber-100 text-amber-800 rounded-full font-semibold">★ Antrian Prioritas</div>
        @endif
    </div>

    <div class="mt-6 p-4 bg-slate-50 rounded-xl text-xs text-slate-600">
        Mohon menunggu di ruang tunggu. Nama Anda akan dipanggil melalui layar TV lobi.
    </div>

    <div class="mt-6 flex gap-2 no-print">
        <button onclick="window.print()" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl font-semibold">🖨 Cetak Tiket</button>
        <a href="{{ route('kiosk.index') }}" class="flex-1 px-4 py-3 bg-slate-200 text-slate-700 rounded-xl font-semibold">Selesai</a>
    </div>
</div>

<script>
    // Auto-print + redirect back ke kiosk setelah 30 detik
    setTimeout(() => window.print(), 600);
    setTimeout(() => window.location = "{{ route('kiosk.index') }}", 30000);
</script>

</body>
</html>
