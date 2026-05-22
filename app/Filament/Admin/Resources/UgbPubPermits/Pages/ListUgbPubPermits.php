<?php

namespace App\Filament\Admin\Resources\UgbPubPermits\Pages;

use App\Filament\Admin\Resources\UgbPubPermits\UgbPubPermitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUgbPubPermits extends ListRecords
{
    protected static string $resource = UgbPubPermitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
