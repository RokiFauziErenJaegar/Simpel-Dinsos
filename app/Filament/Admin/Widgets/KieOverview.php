<?php

namespace App\Filament\Admin\Widgets;

use App\Models\KieConsultation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

/**
 * Ringkasan minimalis Konsultasi Warga (KIE) — counter TERPISAH dari 16 layanan.
 * Menonjolkan capaian KIE hari ini (fitur 2).
 */
class KieOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Konsultasi Warga (KIE)';

    protected ?string $description = 'Pendokumentasian konsultasi warga — di luar 16 layanan';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $s = Cache::remember('kie.overview.v1', 60, function () {
            $today = KieConsultation::whereDate('created_at', today())->count();
            $todayDinsos = KieConsultation::whereDate('created_at', today())->where('location', 'dinsos')->count();
            $todayMpp = KieConsultation::whereDate('created_at', today())->where('location', 'mpp')->count();
            $month = KieConsultation::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();

            return compact('today', 'todayDinsos', 'todayMpp', 'month');
        });

        return [
            Stat::make('Konsultasi Hari Ini', number_format($s['today']))
                ->description('Dinsos '.$s['todayDinsos'].' · MPP '.$s['todayMpp'])
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('primary'),

            Stat::make('KIE Bulan Ini', number_format($s['month']))
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }
}
