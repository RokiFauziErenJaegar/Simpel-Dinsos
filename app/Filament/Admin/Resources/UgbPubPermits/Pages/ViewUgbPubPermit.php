<?php

namespace App\Filament\Admin\Resources\UgbPubPermits\Pages;

use App\Filament\Admin\Resources\UgbPubPermits\UgbPubPermitResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUgbPubPermit extends ViewRecord
{
    protected static string $resource = UgbPubPermitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
