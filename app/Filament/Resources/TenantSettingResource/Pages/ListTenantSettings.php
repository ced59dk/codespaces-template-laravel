<?php

namespace App\Filament\Resources\TenantSettingResource\Pages;

use App\Filament\Resources\TenantSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenantSettings extends ListRecords
{
    protected static string $resource = TenantSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
