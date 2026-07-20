<?php

namespace App\Filament\Admin\Resources\Kie\Pages;

use App\Filament\Admin\Resources\Kie\KieConsultationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewKieConsultation extends ViewRecord
{
    protected static string $resource = KieConsultationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
