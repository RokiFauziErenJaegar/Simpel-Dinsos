<?php

namespace App\Filament\Admin\Resources\Kie\Schemas;

use App\Enums\ServiceLocation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KieConsultationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Warga')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('phone')
                        ->label('No. WhatsApp')
                        ->required()
                        ->maxLength(20)
                        ->helperText('Format 08xxx / 62xxx.'),
                    TextInput::make('nik')
                        ->label('NIK')
                        ->maxLength(16),
                    TextInput::make('address')
                        ->label('Alamat')
                        ->maxLength(255),
                ]),

            Section::make('Konsultasi')
                ->columns(2)
                ->schema([
                    TextInput::make('topic')
                        ->label('Topik')
                        ->maxLength(100),
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'registered' => 'Terdaftar',
                            'served' => 'Sedang dilayani',
                            'done' => 'Selesai',
                        ])
                        ->default('registered')
                        ->required(),
                    Select::make('location')
                        ->label('Lokasi Layanan')
                        ->options(ServiceLocation::options())
                        ->placeholder('— belum ditentukan —')
                        ->helperText('Otomatis terisi mengikuti lokasi petugas saat ditangani.'),
                    Select::make('handled_by')
                        ->label('Ditangani oleh')
                        ->relationship('handler', 'name')
                        ->searchable()
                        ->preload()
                        ->placeholder('— belum —'),
                    Textarea::make('description')
                        ->label('Uraian Keperluan')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
