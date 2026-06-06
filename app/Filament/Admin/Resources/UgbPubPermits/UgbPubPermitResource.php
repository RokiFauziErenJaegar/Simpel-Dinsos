<?php

namespace App\Filament\Admin\Resources\UgbPubPermits;

use App\Filament\Admin\Resources\UgbPubPermits\Pages\CreateUgbPubPermit;
use App\Filament\Admin\Resources\UgbPubPermits\Pages\EditUgbPubPermit;
use App\Filament\Admin\Resources\UgbPubPermits\Pages\ListUgbPubPermits;
use App\Filament\Admin\Resources\UgbPubPermits\Pages\ViewUgbPubPermit;
use App\Filament\Admin\Resources\UgbPubPermits\Schemas\UgbPubPermitForm;
use App\Filament\Admin\Resources\UgbPubPermits\Schemas\UgbPubPermitInfolist;
use App\Filament\Admin\Resources\UgbPubPermits\Tables\UgbPubPermitsTable;
use App\Models\UgbPubPermit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class UgbPubPermitResource extends Resource
{
    protected static ?string $model = UgbPubPermit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?string $navigationLabel = 'Perizinan UGB/PUB';

    protected static ?string $modelLabel = 'Izin UGB/PUB';

    protected static ?string $pluralModelLabel = 'Perizinan UGB/PUB';

    protected static ?int $navigationSort = 11;

    protected static ?string $recordTitleAttribute = 'organization';

    public static function getNavigationBadge(): ?string
    {
        return (string) Cache::remember(
            'nav.badge.ugb',
            60,
            fn () => UgbPubPermit::where('status', 'diajukan')->count()
        );
    }

    public static function form(Schema $schema): Schema
    {
        return UgbPubPermitForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UgbPubPermitInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UgbPubPermitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUgbPubPermits::route('/'),
            'create' => CreateUgbPubPermit::route('/create'),
            'view' => ViewUgbPubPermit::route('/{record}'),
            'edit' => EditUgbPubPermit::route('/{record}/edit'),
        ];
    }
}
