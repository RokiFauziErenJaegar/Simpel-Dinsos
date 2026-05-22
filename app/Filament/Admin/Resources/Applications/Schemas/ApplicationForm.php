<?php

namespace App\Filament\Admin\Resources\Applications\Schemas;

use App\Enums\ApplicationStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                Select::make('service_type_id')
                    ->relationship('serviceType', 'name')
                    ->required(),
                TextInput::make('applicant_user_id')
                    ->required()
                    ->numeric(),
                Select::make('current_handler_id')
                    ->relationship('currentHandler', 'name'),
                TextInput::make('beneficiary_name')
                    ->required(),
                TextInput::make('beneficiary_nik'),
                TextInput::make('beneficiary_relation')
                    ->required()
                    ->default('diri_sendiri'),
                Textarea::make('purpose')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(ApplicationStatus::class)
                    ->default('submitted')
                    ->required(),
                TextInput::make('current_step')
                    ->required()
                    ->default('verifikasi_loket'),
                TextInput::make('priority')
                    ->required()
                    ->default('normal'),
                DateTimePicker::make('submitted_at'),
                DateTimePicker::make('sla_due_at'),
                DateTimePicker::make('completed_at'),
                Textarea::make('rejection_reason')
                    ->columnSpanFull(),
                Textarea::make('meta')
                    ->columnSpanFull(),
            ]);
    }
}
