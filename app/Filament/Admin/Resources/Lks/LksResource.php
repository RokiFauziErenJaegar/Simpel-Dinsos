<?php

namespace App\Filament\Admin\Resources\Lks;

use App\Filament\Admin\Resources\Lks\Pages\CreateLks;
use App\Filament\Admin\Resources\Lks\Pages\EditLks;
use App\Filament\Admin\Resources\Lks\Pages\ListLks;
use App\Filament\Admin\Resources\Lks\Pages\ViewLks;
use App\Filament\Admin\Resources\Lks\Schemas\LksForm;
use App\Filament\Admin\Resources\Lks\Schemas\LksInfolist;
use App\Filament\Admin\Resources\Lks\Tables\LksTable;
use App\Models\Lks;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LksResource extends Resource
{
    protected static ?string $model = Lks::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'LKS / Yayasan / Panti';

    protected static ?string $modelLabel = 'LKS';

    protected static ?string $pluralModelLabel = 'LKS Terdaftar';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return LksForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LksInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LksTable::configure($table);
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
            'index' => ListLks::route('/'),
            'create' => CreateLks::route('/create'),
            'view' => ViewLks::route('/{record}'),
            'edit' => EditLks::route('/{record}/edit'),
        ];
    }
}
