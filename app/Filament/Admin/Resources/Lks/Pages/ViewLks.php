<?php

namespace App\Filament\Admin\Resources\Lks\Pages;

use App\Filament\Admin\Resources\Lks\LksResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLks extends ViewRecord
{
    protected static string $resource = LksResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
