<?php

namespace App\Filament\Admin\Resources\Kie\Tables;

use App\Enums\ServiceLocation;
use App\Models\KieConsultation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KieConsultationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('topic')
                    ->label('Topik')
                    ->placeholder('—')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ServiceLocation ? $state->shortLabel() : '—')
                    ->color(fn ($state) => $state instanceof ServiceLocation ? $state->color() : 'gray')
                    ->placeholder('Online'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'registered' => 'Terdaftar',
                        'served' => 'Dilayani',
                        'done' => 'Selesai',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'registered' => 'warning',
                        'served' => 'info',
                        'done' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('handler.name')
                    ->label('Petugas')
                    ->placeholder('— belum —')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'registered' => 'Terdaftar',
                        'served' => 'Sedang dilayani',
                        'done' => 'Selesai',
                    ]),
                SelectFilter::make('location')
                    ->label('Lokasi')
                    ->options(ServiceLocation::options()),
                Filter::make('today')
                    ->label('Hari ini')
                    ->query(fn (Builder $q) => $q->whereDate('created_at', today())),
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat'),
                EditAction::make()->label('Edit'),

                Action::make('serve')
                    ->label('Tandai Ditangani')
                    ->icon('heroicon-o-hand-raised')
                    ->color('success')
                    ->visible(fn (KieConsultation $record) => $record->status === 'registered')
                    ->requiresConfirmation()
                    ->modalHeading('Tandai konsultasi ini sedang Anda tangani?')
                    ->modalDescription('Lokasi layanan akan otomatis mengikuti lokasi akun Anda bila belum ditentukan.')
                    ->action(function (KieConsultation $record) {
                        $user = auth()->user();
                        $record->handled_by = $user?->id;
                        $record->status = 'served';
                        $record->served_at = now();
                        if ($record->location === null && $user?->location !== null) {
                            $record->location = $user->location instanceof ServiceLocation
                                ? $user->location->value
                                : $user->location;
                        }
                        $record->save();

                        Notification::make()->success()->title('Konsultasi ditandai sedang dilayani')->send();
                    }),

                Action::make('finish')
                    ->label('Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->visible(fn (KieConsultation $record) => in_array($record->status, ['registered', 'served']))
                    ->requiresConfirmation()
                    ->action(function (KieConsultation $record) {
                        $user = auth()->user();
                        if (! $record->handled_by) {
                            $record->handled_by = $user?->id;
                        }
                        if ($record->location === null && $user?->location !== null) {
                            $record->location = $user->location instanceof ServiceLocation
                                ? $user->location->value
                                : $user->location;
                        }
                        $record->status = 'done';
                        $record->save();

                        Notification::make()->success()->title('Konsultasi ditandai selesai')->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
