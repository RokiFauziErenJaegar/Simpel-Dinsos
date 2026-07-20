<?php

namespace App\Filament\Admin\Pages;

use App\Services\SkmReportGenerator;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class LaporanSkm extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?string $navigationLabel = 'Laporan SKM';

    protected static ?string $title = 'Laporan Survei Kepuasan Masyarakat';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.admin.pages.laporan-skm';

    public ?string $reportPath = null;

    protected function getHeaderActions(): array
    {
        return [
            // Ekspor bulanan
            Action::make('monthly')
                ->label('Laporan Bulanan')
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->schema([
                    Select::make('month')
                        ->label('Periode')
                        ->options(collect(range(0, 11))->mapWithKeys(function ($i) {
                            $d = now()->subMonths($i);

                            return [$d->format('Y-m') => $d->translatedFormat('F Y')];
                        })->all())
                        ->default(now()->format('Y-m'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $month = Carbon::createFromFormat('Y-m', $data['month']);
                    $this->buildReport(
                        $month->copy()->startOfMonth(),
                        $month->copy()->endOfMonth(),
                        $month->translatedFormat('F Y'),
                    );
                }),

            // Ekspor rentang tanggal custom
            Action::make('custom')
                ->label('Rentang Tanggal')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('gray')
                ->schema([
                    DatePicker::make('from')
                        ->label('Dari Tanggal')
                        ->default(now()->startOfMonth())
                        ->required(),
                    DatePicker::make('to')
                        ->label('Sampai Tanggal')
                        ->default(now())
                        ->required()
                        ->afterOrEqual('from'),
                ])
                ->action(function (array $data) {
                    $from = Carbon::parse($data['from'])->startOfDay();
                    $to = Carbon::parse($data['to'])->endOfDay();
                    $this->buildReport($from, $to, $from->translatedFormat('d M Y').' – '.$to->translatedFormat('d M Y'));
                }),
        ];
    }

    protected function buildReport(Carbon $from, Carbon $to, string $label): void
    {
        $signer = auth()->user()?->name ?? 'Kepala Dinas Sosial';
        $path = app(SkmReportGenerator::class)->generate($from, $to, $label, $signer);
        $this->reportPath = $path;

        Notification::make()
            ->success()
            ->title('Laporan SKM '.$label.' siap')
            ->body('PDF tersimpan di storage/'.$path)
            ->actions([
                Action::make('open')
                    ->label('Buka PDF')
                    ->url(asset('storage/'.$path), shouldOpenInNewTab: true),
            ])
            ->persistent()
            ->send();
    }
}
