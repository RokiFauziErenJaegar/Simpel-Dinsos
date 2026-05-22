<?php

namespace App\Filament\Admin\Pages;

use App\Services\MonthlyReportGenerator;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class LaporanBulanan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Laporan Bulanan';

    protected static ?string $title = 'Laporan Bulanan untuk Bupati';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.admin.pages.laporan-bulanan';

    public ?string $reportPath = null;

    public ?string $selectedMonth = null;

    public function mount(): void
    {
        $this->selectedMonth = now()->format('Y-m');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Buat Laporan PDF')
                ->icon('heroicon-o-document-arrow-down')
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
                    $month = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
                    $signer = auth()->user()?->name ?? 'Kepala Dinas Sosial';

                    $path = app(MonthlyReportGenerator::class)->generate($month, $signer);
                    $this->reportPath = $path;

                    Notification::make()
                        ->success()
                        ->title('Laporan '.$month->translatedFormat('F Y').' siap')
                        ->body('PDF tersimpan di storage/'.$path)
                        ->actions([
                            Action::make('open')
                                ->label('Buka PDF')
                                ->url(asset('storage/'.$path), shouldOpenInNewTab: true),
                        ])
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
