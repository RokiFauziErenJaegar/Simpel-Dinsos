<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\ServiceLocation;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->toggleable(),
                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof UserRole ? $state->label() : $state)
                    ->color(fn ($state) => ($state instanceof UserRole ? $state->value : $state) === 'admin' ? 'danger' : 'primary'),
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ServiceLocation ? $state->shortLabel() : '—')
                    ->color(fn ($state) => $state instanceof ServiceLocation ? $state->color() : 'gray')
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                IconColumn::make('two_factor_confirmed_at')
                    ->label('2FA')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('last_login_at')
                    ->label('Login Terakhir')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Peran')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $r) => [$r->value => $r->label()])->all()),
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
                    ->label(fn (User $record) => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (User $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record) => ($record->is_active ? 'Nonaktifkan' : 'Aktifkan').' akun '.$record->name.'?')
                    ->disabled(fn (User $record) => $record->id === auth()->id())
                    ->action(function (User $record) {
                        if ($record->id === auth()->id()) {
                            Notification::make()->danger()->title('Tidak dapat menonaktifkan akun sendiri.')->send();

                            return;
                        }
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()
                            ->success()
                            ->title('Akun '.($record->is_active ? 'diaktifkan' : 'dinonaktifkan'))
                            ->send();
                    }),

                Action::make('reset2fa')
                    ->label('Reset 2FA')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('gray')
                    ->visible(fn (User $record) => ! is_null($record->two_factor_confirmed_at))
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record) => 'Reset 2FA untuk '.$record->name.'?')
                    ->modalDescription('Berguna saat pergantian jabatan: pemilik akun baru dapat mendaftarkan ulang 2FA-nya.')
                    ->action(function (User $record) {
                        $record->forceFill([
                            'two_factor_secret' => null,
                            'two_factor_confirmed_at' => null,
                            'two_factor_recovery_codes' => null,
                        ])->save();
                        Notification::make()->success()->title('2FA direset. Pengguna perlu mendaftarkan ulang.')->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
