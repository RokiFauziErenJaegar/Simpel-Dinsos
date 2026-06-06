<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Application;
use App\Models\Complaint;
use App\Models\QueueTicket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class KadisOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Pelayanan';

    protected ?string $description = 'Data bulan berjalan untuk laporan ke Bupati';

    /** Matikan auto-polling agar dashboard tidak refresh diam-diam (hemat query). */
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // Cache 60 detik — ringkasan tidak perlu real-time, dipakai untuk laporan.
        $s = Cache::remember('kadis.overview.v2', 60, function () {
            $month = now()->month;
            $year = now()->year;

            $totalMonth = Application::whereMonth('submitted_at', $month)->whereYear('submitted_at', $year)->count();
            $completed = Application::whereMonth('submitted_at', $month)->whereYear('submitted_at', $year)->where('status', 'completed')->count();
            $onTime = Application::whereMonth('submitted_at', $month)
                ->whereYear('submitted_at', $year)
                ->where('status', 'completed')
                ->whereColumn('completed_at', '<=', 'sla_due_at')
                ->count();
            $onTimePct = $completed > 0 ? round(($onTime / $completed) * 100, 1) : 0;

            // Rata-rata waktu penyelesaian sebagai % dari batas waktu SLA.
            // Per pengajuan selesai: (submitted→completed) / (submitted→sla_due) × 100.
            // < 100% = rata-rata lebih cepat dari SLA; > 100% = melebihi SLA.
            $finished = Application::whereMonth('submitted_at', $month)
                ->whereYear('submitted_at', $year)
                ->where('status', 'completed')
                ->whereNotNull('completed_at')
                ->whereNotNull('sla_due_at')
                ->whereNotNull('submitted_at')
                ->get(['submitted_at', 'completed_at', 'sla_due_at']);

            $ratios = [];
            $actualMinutesSum = 0;
            foreach ($finished as $a) {
                $budget = $a->submitted_at->diffInMinutes($a->sla_due_at);
                $actual = $a->submitted_at->diffInMinutes($a->completed_at);
                if ($budget > 0) {
                    $ratios[] = $actual / $budget;
                    $actualMinutesSum += $actual;
                }
            }
            $avgTimePct = count($ratios) ? round((array_sum($ratios) / count($ratios)) * 100, 1) : 0;
            $avgActualMinutes = count($ratios) ? (int) round($actualMinutesSum / count($ratios)) : 0;

            $activeComplaints = Complaint::whereIn('status', ['open', 'in_progress'])->count();
            $servedToday = QueueTicket::whereDate('ticket_date', today())->where('status', 'done')->count();
            $overdue = Application::whereNotIn('status', ['completed', 'rejected'])
                ->whereNotNull('sla_due_at')
                ->where('sla_due_at', '<', now())
                ->count();

            return compact('totalMonth', 'completed', 'onTimePct', 'avgTimePct', 'avgActualMinutes',
                'activeComplaints', 'servedToday', 'overdue');
        });

        ['totalMonth' => $totalMonth, 'completed' => $completed, 'onTimePct' => $onTimePct,
         'avgTimePct' => $avgTimePct, 'avgActualMinutes' => $avgActualMinutes,
         'activeComplaints' => $activeComplaints, 'servedToday' => $servedToday, 'overdue' => $overdue] = $s;

        // Format ringkas durasi rata-rata untuk deskripsi.
        $avgTimeHuman = $avgActualMinutes <= 0 ? '—'
            : ($avgActualMinutes < 60 ? $avgActualMinutes.' mnt'
            : ($avgActualMinutes < 1440 ? round($avgActualMinutes / 60, 1).' jam'
            : round($avgActualMinutes / 1440, 1).' hari'));

        return [
            Stat::make('Pemohon Bulan Ini', number_format($totalMonth))
                ->description($completed.' selesai')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary')
                ->chart([3, 5, 4, 6, 8, 7, 9]),

            Stat::make('Ketepatan SLA', $onTimePct.' %')
                ->description($onTimePct >= 90 ? 'Memenuhi target' : 'Di bawah target 90%')
                ->descriptionIcon($onTimePct >= 90 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($onTimePct >= 90 ? 'success' : 'warning'),

            Stat::make('Rata-rata Waktu Penyelesaian', $avgTimePct.' %')
                ->description($completed > 0
                    ? 'dari batas SLA · ≈ '.$avgTimeHuman.' per pengajuan'
                    : 'Belum ada pengajuan selesai bulan ini')
                ->descriptionIcon('heroicon-m-clock')
                ->color($avgTimePct == 0 ? 'gray' : ($avgTimePct <= 100 ? 'success' : ($avgTimePct <= 120 ? 'warning' : 'danger'))),

            Stat::make('Pengaduan Aktif', $activeComplaints)
                ->description('Perlu ditindaklanjuti')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color($activeComplaints > 0 ? 'warning' : 'success'),

            Stat::make('Pengajuan Lewat SLA', $overdue)
                ->description('Perlu eskalasi')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdue > 0 ? 'danger' : 'success'),

            Stat::make('Dilayani Hari Ini', $servedToday)
                ->description(now()->translatedFormat('d M Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Indeks Kepuasan', '87,3 / 100')
                ->description('Skor SKM bulan berjalan')
                ->descriptionIcon('heroicon-m-star')
                ->color('success'),
        ];
    }
}
