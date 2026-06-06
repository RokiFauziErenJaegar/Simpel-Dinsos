<?php

namespace App\Filament\Admin\Resources\Lks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LksForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('registration_number')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('type')
                    ->required()
                    ->default('LKS'),
                TextInput::make('address')
                    ->required(),
                Select::make('kecamatan_id')
                    ->relationship('kecamatan', 'name'),
                TextInput::make('contact_person'),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('akta_notaris'),
                TextInput::make('npwp'),
                TextInput::make('kemenkumham_no'),
                DatePicker::make('registered_at'),
                DatePicker::make('valid_until'),
                TextInput::make('client_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('status')
                    ->required()
                    ->default('aktif'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
