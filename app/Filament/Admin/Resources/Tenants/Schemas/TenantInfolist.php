<?php

namespace App\Filament\Admin\Resources\Tenants\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TenantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('slug'),
                TextEntry::make('name'),
                TextEntry::make('kode_wilayah'),
                TextEntry::make('instansi'),
                TextEntry::make('alamat'),
                TextEntry::make('kode_pos'),
                TextEntry::make('call_center'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('maklumat'),
                TextEntry::make('kop_logo'),
                TextEntry::make('primary_color'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
