<?php

namespace App\Filament\Admin\Resources\Kie\Pages;

use App\Filament\Admin\Resources\Kie\KieConsultationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditKieConsultation extends EditRecord
{
    protected static string $resource = KieConsultationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
