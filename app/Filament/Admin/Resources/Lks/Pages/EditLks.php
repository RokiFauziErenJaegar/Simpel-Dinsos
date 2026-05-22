<?php

namespace App\Filament\Admin\Resources\Lks\Pages;

use App\Filament\Admin\Resources\Lks\LksResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLks extends EditRecord
{
    protected static string $resource = LksResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
