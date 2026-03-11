<?php

namespace App\Filament\Resources\TenantSettingResource\Pages;

use App\Filament\Resources\TenantSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTenantSetting extends CreateRecord
{
    protected static string $resource = TenantSettingResource::class;
}
