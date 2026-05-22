<?php

namespace App\Filament\Admin\Resources\Lks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LksInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('registration_number'),
                TextEntry::make('name'),
                TextEntry::make('type'),
                TextEntry::make('address'),
                TextEntry::make('kecamatan.name')
                    ->numeric(),
                TextEntry::make('contact_person'),
                TextEntry::make('phone'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('akta_notaris'),
                TextEntry::make('npwp'),
                TextEntry::make('kemenkumham_no'),
                TextEntry::make('registered_at')
                    ->date(),
                TextEntry::make('valid_until')
                    ->date(),
                TextEntry::make('client_count')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
