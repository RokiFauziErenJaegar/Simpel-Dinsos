<?php

namespace App\Filament\Admin\Resources\Tenants\Tables;

use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('is_active', 'desc')
            ->columns([
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('kode_wilayah')
                    ->label('Kode Wilayah')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('instansi')
                    ->label('Instansi')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('call_center')
                    ->label('Call Center')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Non-aktif'),
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat'),
                EditAction::make()->label('Edit'),

                Action::make('toggle')
                    ->label(fn (Tenant $record) => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (Tenant $record) => $record->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Tenant $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Tenant $record) => ($record->is_active ? 'Nonaktifkan' : 'Aktifkan').' tenant '.$record->name.'?')
                    ->modalDescription(fn (Tenant $record) => $record->is_active
                        ? 'Warga kabupaten ini tidak akan dapat mengakses sistem sampai diaktifkan kembali.'
                        : 'Tenant akan tersedia di subdomain & header X-Tenant: '.$record->slug)
                    ->action(function (Tenant $record) {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()
                            ->success()
                            ->title('Tenant '.($record->is_active ? 'diaktifkan' : 'dinonaktifkan'))
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
