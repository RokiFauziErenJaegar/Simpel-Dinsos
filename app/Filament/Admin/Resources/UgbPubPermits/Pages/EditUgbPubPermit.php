<?php

namespace App\Filament\Admin\Resources\UgbPubPermits\Pages;

use App\Filament\Admin\Resources\UgbPubPermits\UgbPubPermitResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUgbPubPermit extends EditRecord
{
    protected static string $resource = UgbPubPermitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
