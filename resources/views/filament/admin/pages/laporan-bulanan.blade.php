<x-filament-panels::page>
    <div class="space-y-4">
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Laporan Bulanan untuk Bupati</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Buat laporan PDF resmi berisi KPI utama, performa SLA per layanan, statistik pengaduan, dan ringkasan eksekutif —
                siap dilampirkan ke Nota Dinas / dikirim ke Sekretariat Daerah.
            </p>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-lg bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950 p-4">
                    <div class="text-xs font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wide">Periode default</div>
                    <div class="mt-1 text-2xl font-bold text-blue-900 dark:text-blue-100">{{ now()->translatedFormat('F Y') }}</div>
                </div>
                <div class="rounded-lg bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950 dark:to-teal-950 p-4">
                    <div class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wide">Format</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-900 dark:text-emerald-100">PDF A4</div>
                </div>
                <div class="rounded-lg bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950 dark:to-orange-950 p-4">
                    <div class="text-xs font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wide">Tanda tangan</div>
                    <div class="mt-1 text-2xl font-bold text-amber-900 dark:text-amber-100">Otomatis</div>
                </div>
            </div>

            @if($reportPath)
                <div class="mt-6 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-950 border border-emerald-200 dark:border-emerald-800">
                    <div class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">✓ Laporan terakhir berhasil dibuat</div>
                    <div class="mt-2">
                        <a href="{{ asset('storage/'.$reportPath) }}" target="_blank" class="text-emerald-700 dark:text-emerald-400 underline text-sm font-medium">Buka {{ basename($reportPath) }} →</a>
                    </div>
                </div>
            @endif

            <div class="mt-6 text-sm text-gray-600 dark:text-gray-400 space-y-2">
                <p><strong>Isi laporan otomatis:</strong></p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Ringkasan KPI (total pengajuan, selesai, ketepatan SLA, indeks kepuasan)</li>
                    <li>Tabel performa per layanan (kode L01–L16)</li>
                    <li>Statistik pengaduan masyarakat</li>
                    <li>Narasi eksekutif yang dapat diedit sebelum cetak</li>
                    <li>Kop, kop kaki, & blok tanda tangan Kepala Dinas</li>
                </ul>
            </div>
        </div>
    </div>
</x-filament-panels::page>
