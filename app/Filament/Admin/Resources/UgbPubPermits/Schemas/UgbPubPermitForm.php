<?php

namespace App\Filament\Admin\Resources\UgbPubPermits\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UgbPubPermitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('permit_number')
                    ->required(),
                TextInput::make('type')
                    ->required(),
                TextInput::make('organization')
                    ->required(),
                TextInput::make('pic_name')
                    ->required(),
                TextInput::make('pic_phone')
                    ->tel()
                    ->required(),
                TextInput::make('pic_email')
                    ->email(),
                TextInput::make('legal_form'),
                TextInput::make('akta_notaris'),
                TextInput::make('npwp'),
                TextInput::make('nib'),
                Textarea::make('purpose')
                    ->required()
                    ->columnSpanFull(),
                DatePicker::make('start_date')
                    ->required(),
                DatePicker::make('end_date')
                    ->required(),
                TextInput::make('area_scope')
                    ->required(),
                TextInput::make('target_amount')
                    ->numeric(),
                TextInput::make('collection_method'),
                TextInput::make('distribution_plan'),
                Select::make('kecamatan_id')
                    ->relationship('kecamatan', 'name'),
                TextInput::make('location_address')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('diajukan'),
                Select::make('reviewed_by_id')
                    ->relationship('reviewedBy', 'name'),
                DateTimePicker::make('reviewed_at'),
                Textarea::make('review_notes')
                    ->columnSpanFull(),
                TextInput::make('rekomendasi_file_path'),
            ]);
    }
}
