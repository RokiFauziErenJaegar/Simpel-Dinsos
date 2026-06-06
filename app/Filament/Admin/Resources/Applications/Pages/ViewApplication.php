<?php

namespace App\Filament\Admin\Resources\Applications\Pages;

use App\Filament\Admin\Resources\Applications\ApplicationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    /**
     * Eager-load relasi yang dipakai infolist (logs.user, dokumen, surat) agar
     * tidak terjadi N+1 saat me-render timeline & dokumen terbitan.
     */
    protected function resolveRecord(int|string $key): Model
    {
        return static::getResource()::getModel()::query()
            ->with(['serviceType', 'applicant', 'documents', 'logs.user', 'outputDocument.signedBy'])
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
