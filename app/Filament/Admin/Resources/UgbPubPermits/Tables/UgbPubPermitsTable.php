<?php

namespace App\Filament\Admin\Resources\UgbPubPermits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UgbPubPermitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('permit_number')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('organization')
                    ->searchable(),
                TextColumn::make('pic_name')
                    ->searchable(),
                TextColumn::make('pic_phone')
                    ->searchable(),
                TextColumn::make('pic_email')
                    ->searchable(),
                TextColumn::make('legal_form')
                    ->searchable(),
                TextColumn::make('akta_notaris')
                    ->searchable(),
                TextColumn::make('npwp')
                    ->searchable(),
                TextColumn::make('nib')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('area_scope')
                    ->searchable(),
                TextColumn::make('target_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('collection_method')
                    ->searchable(),
                TextColumn::make('distribution_plan')
                    ->searchable(),
                TextColumn::make('kecamatan.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('location_address')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('reviewedBy.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('rekomendasi_file_path')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
