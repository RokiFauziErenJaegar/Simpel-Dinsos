<?php

namespace App\Filament\Admin\Resources\Lks\Pages;

use App\Filament\Admin\Resources\Lks\LksResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLks extends ListRecords
{
    protected static string $resource = LksResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
