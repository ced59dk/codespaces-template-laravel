<?php

namespace App\Filament\Resources\TenantSettingResource\Pages;

use App\Filament\Resources\TenantSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenantSetting extends EditRecord
{
    protected static string $resource = TenantSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
