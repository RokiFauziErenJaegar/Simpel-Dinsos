<?php

namespace App\Filament\Admin\Resources\Kie\Pages;

use App\Filament\Admin\Resources\Kie\KieConsultationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKieConsultations extends ListRecords
{
    protected static string $resource = KieConsultationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Catat Konsultasi'),
        ];
    }
}
