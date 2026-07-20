<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\ServiceLocation;
use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(150),
                    TextInput::make('phone')
                        ->label('No. WhatsApp')
                        ->tel()
                        ->maxLength(20)
                        ->helperText('Format 08xxx / 62xxx.'),
                ]),

            Section::make('Peran & Akses')
                ->columns(2)
                ->schema([
                    Select::make('role')
                        ->label('Jabatan / Peran')
                        ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $r) => [$r->value => $r->label()])->all())
                        ->required()
                        ->native(false)
                        ->helperText('Untuk pergantian jabatan pimpinan, ubah peran di sini.'),
                    Select::make('location')
                        ->label('Lokasi Kerja')
                        ->options(ServiceLocation::options())
                        ->native(false)
                        ->helperText('Menentukan lokasi pelayanan (Dinsos/MPP) yang ditangani petugas ini.'),
                    Toggle::make('is_active')
                        ->label('Akun Aktif')
                        ->default(true)
                        ->helperText('Nonaktifkan untuk mencabut akses tanpa menghapus akun.')
                        ->columnSpanFull(),
                ]),

            Section::make('Data Kepegawaian (untuk penanda tangan)')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextInput::make('jabatan_full')
                        ->label('Jabatan Lengkap')
                        ->maxLength(150)
                        ->placeholder('Kepala Dinas Sosial Kabupaten Pringsewu'),
                    TextInput::make('nip')
                        ->label('NIP')
                        ->maxLength(30),
                    TextInput::make('pangkat')
                        ->label('Pangkat / Golongan')
                        ->maxLength(60),
                ]),

            Section::make('Keamanan')
                ->schema([
                    TextInput::make('password')
                        ->label('Kata Sandi')
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->helperText('Saat edit: kosongkan bila tidak ingin mengubah kata sandi.'),
                ]),
        ]);
    }
}
