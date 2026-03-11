<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => optional(Filament::auth()->user())->tenant_id),
                Forms\Components\TextInput::make('code_compta')
                    ->label('Code compta')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel()
                    ->maxLength(50),
                Forms\Components\Textarea::make('address')
                    ->label('Adresse')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('vat_number')
                    ->label('Numéro TVA')
                    ->maxLength(100),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code_compta')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('vat_number')->searchable(),
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

        $tenantId = optional(Filament::auth()->user())->tenant_id;

        if (! $tenantId) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('tenant_id', $tenantId);
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $tenantId = optional(Filament::auth()->user())->tenant_id;
        $data['tenant_id'] = $tenantId;
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data, $record): array
    {
        $tenantId = optional(Filament::auth()->user())->tenant_id;
        $data['tenant_id'] = $tenantId;
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
