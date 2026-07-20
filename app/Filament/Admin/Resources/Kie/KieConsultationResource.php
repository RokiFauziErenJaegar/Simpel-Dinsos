<?php

namespace App\Filament\Admin\Resources\Kie;

use App\Filament\Admin\Resources\Kie\Pages\CreateKieConsultation;
use App\Filament\Admin\Resources\Kie\Pages\EditKieConsultation;
use App\Filament\Admin\Resources\Kie\Pages\ListKieConsultations;
use App\Filament\Admin\Resources\Kie\Pages\ViewKieConsultation;
use App\Filament\Admin\Resources\Kie\Schemas\KieConsultationForm;
use App\Filament\Admin\Resources\Kie\Tables\KieConsultationsTable;
use App\Models\KieConsultation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

class KieConsultationResource extends Resource
{
    protected static ?string $model = KieConsultation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Konsultasi Warga (KIE)';

    protected static ?string $modelLabel = 'Konsultasi (KIE)';

    protected static ?string $pluralModelLabel = 'Konsultasi Warga (KIE)';

    protected static string|UnitEnum|null $navigationGroup = 'Pelayanan';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember(
            'nav.badge.kie',
            30,
            fn () => (string) KieConsultation::whereDate('created_at', today())->count()
        );
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Konsultasi tercatat hari ini';
    }

    public static function form(Schema $schema): Schema
    {
        return KieConsultationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KieConsultationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKieConsultations::route('/'),
            'create' => CreateKieConsultation::route('/create'),
            'view' => ViewKieConsultation::route('/{record}'),
            'edit' => EditKieConsultation::route('/{record}/edit'),
        ];
    }
}
