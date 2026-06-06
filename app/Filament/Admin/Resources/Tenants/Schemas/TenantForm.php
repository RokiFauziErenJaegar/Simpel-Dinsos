<?php

namespace App\Filament\Admin\Resources\Tenants\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Tenant')
                ->columns(2)
                ->schema([
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(50)
                        ->regex('/^[a-z0-9-]+$/')
                        ->helperText('Lowercase a-z, 0-9, dan -. Dipakai untuk subdomain.')
                        ->disabledOn('edit'),
                    TextInput::make('name')
                        ->label('Nama Kabupaten')
                        ->required()
                        ->maxLength(200),
                    TextInput::make('kode_wilayah')
                        ->label('Kode Wilayah (NIK)')
                        ->maxLength(10)
                        ->helperText('6 digit pertama NIK warga kabupaten ini (mis. 187103 untuk Pringsewu)'),
                    TextInput::make('instansi')
                        ->label('Nama Instansi Lengkap')
                        ->required()
                        ->maxLength(200),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->helperText('Hanya tenant aktif yang dapat diakses warga.')
                        ->columnSpanFull(),
                ]),

            Section::make('Kontak & Alamat')
                ->columns(2)
                ->schema([
                    TextInput::make('alamat')
                        ->label('Alamat Kantor')
                        ->maxLength(500)
                        ->columnSpanFull(),
                    TextInput::make('kode_pos')
                        ->maxLength(10),
                    TextInput::make('call_center')
                        ->label('Call Center / WhatsApp')
                        ->maxLength(30),
                    TextInput::make('email')
                        ->email()
                        ->maxLength(150),
                    TextInput::make('maklumat')
                        ->label('Nomor Maklumat Pelayanan')
                        ->maxLength(100)
                        ->placeholder('920/460/D.04/X/2023'),
                ]),

            Section::make('Branding')
                ->columns(2)
                ->schema([
                    TextInput::make('kop_logo')
                        ->label('Path Logo Kop')
                        ->maxLength(255)
                        ->placeholder('kops/pringsewu.png'),
                    ColorPicker::make('primary_color')
                        ->label('Warna Primer')
                        ->default('#1E4D8C'),
                ]),

            Section::make('Konfigurasi Lanjutan')
                ->collapsed()
                ->schema([
                    Textarea::make('settings')
                        ->label('Settings JSON')
                        ->helperText('Konfigurasi tambahan per-tenant (mis. db_connection untuk mode per-db). Format JSON.')
                        ->rows(4),
                ]),
        ]);
    }
}
