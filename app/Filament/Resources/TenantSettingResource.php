<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantSettingResource\Pages;
use App\Models\TenantSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantSettingResource extends Resource
{
    protected static ?string $model = TenantSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TagsInput::make('accounting_emails')
                    ->label('Emails comptabilité')
                    ->placeholder('add@email.com')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('rounding_rule')
                    ->label('Règle d\'arrondi')
                    ->default('quarter_hour')
                    ->required(),
                Forms\Components\KeyValue::make('csv_mapping')
                    ->label('Mapping CSV')
                    ->keyLabel('col')
                    ->valueLabel('field')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.slug')->label('Tenant')->sortable(),
                Tables\Columns\TextColumn::make('rounding_rule')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $tenantId = optional(auth()->user())->tenant_id;

        if (! $tenantId) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('tenant_id', $tenantId);
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = optional(auth()->user())->tenant_id;
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data, $record): array
    {
        $data['tenant_id'] = optional(auth()->user())->tenant_id;
        return $data;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenantSettings::route('/'),
            'create' => Pages\CreateTenantSetting::route('/create'),
            'edit' => Pages\EditTenantSetting::route('/{record}/edit'),
        ];
    }
}
