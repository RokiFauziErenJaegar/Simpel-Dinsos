<?php

namespace App\Filament\Admin\Resources\UgbPubPermits\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UgbPubPermitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('permit_number'),
                TextEntry::make('type'),
                TextEntry::make('organization'),
                TextEntry::make('pic_name'),
                TextEntry::make('pic_phone'),
                TextEntry::make('pic_email'),
                TextEntry::make('legal_form'),
                TextEntry::make('akta_notaris'),
                TextEntry::make('npwp'),
                TextEntry::make('nib'),
                TextEntry::make('start_date')
                    ->date(),
                TextEntry::make('end_date')
                    ->date(),
                TextEntry::make('area_scope'),
                TextEntry::make('target_amount')
                    ->numeric(),
                TextEntry::make('collection_method'),
                TextEntry::make('distribution_plan'),
                TextEntry::make('kecamatan.name')
                    ->numeric(),
                TextEntry::make('location_address'),
                TextEntry::make('status'),
                TextEntry::make('reviewedBy.name')
                    ->numeric(),
                TextEntry::make('reviewed_at')
                    ->dateTime(),
                TextEntry::make('rekomendasi_file_path'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
