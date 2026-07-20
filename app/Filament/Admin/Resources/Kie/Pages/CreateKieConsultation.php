<?php

namespace App\Filament\Admin\Resources\Kie\Pages;

use App\Filament\Admin\Resources\Kie\KieConsultationResource;
use App\Models\KieConsultation;
use Filament\Resources\Pages\CreateRecord;

class CreateKieConsultation extends CreateRecord
{
    protected static string $resource = KieConsultationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Nomor registrasi otomatis untuk pencatatan oleh petugas (walk-in).
        $data['code'] = KieConsultation::generateCode();

        // Bila petugas tidak set lokasi, ikuti lokasi akun petugas.
        if (empty($data['location']) && ($loc = auth()->user()?->location)) {
            $data['location'] = $loc instanceof \App\Enums\ServiceLocation ? $loc->value : $loc;
        }

        return $data;
    }
}
