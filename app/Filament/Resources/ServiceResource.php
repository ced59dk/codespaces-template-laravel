<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->maxLength(255),

            Forms\Components\Hidden::make('tenant_id')
                ->default(fn () => optional(Filament::auth()->user())->tenant_id),

            Forms\Components\TextInput::make('code_article_compta')
                ->label('Code article compta')
                ->required()
                ->maxLength(100),

            Forms\Components\Select::make('unit_type')
                ->label('Type d\'unité')
                ->options([
                    'hour' => 'hour',
                    'day' => 'day',
                    'fixed' => 'fixed',
                ])
                ->default('hour')
                ->required(),

            Forms\Components\TextInput::make('unit_price_default')
                ->label('Prix unitaire par défaut (tarif jour)')
                ->numeric()
                ->default(0)
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    $unit = (float) $state;
                    $day  = (float) ($get('rate_day_hour') ?? 0);

                    // Sync -> rate_day_hour
                    if ($unit > 0 && abs($day - $unit) > 0.0001) {
                        $set('rate_day_hour', $unit);
                    }
                }),

            Forms\Components\TextInput::make('vat_rate_default')
                ->label('TVA par défaut (%)')
                ->numeric()
                ->default(20.00)
                ->required(),

            Forms\Components\Toggle::make('rates_auto')
                ->label('Auto-calcul des tarifs (selon règles entreprise)')
                ->default(true)
                ->live(),

            Forms\Components\Section::make('Tarifs horaires (HT)')
                ->schema([
                    Forms\Components\TextInput::make('rate_day_hour')
                        ->label('Jour')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $j = (float) $state;
                            $unit = (float) ($get('unit_price_default') ?? 0);

                            // Sync -> unit_price_default
                            if ($j > 0 && abs($unit - $j) > 0.0001) {
                                $set('unit_price_default', $j);
                            }

                            if (! $get('rates_auto')) {
                                return;
                            }

                            $round = fn (float $v) => round($v, 2);

                            // Règles métier
                            $night = $round($j * 1.12);
                            $sunDay = $night;                 // dimanche = nuit
                            $sunNight = $round($sunDay * 1.14);

                            $holDay = $round($j * 2);
                            $holNight = $round($night * 2);

                            $sunHolDay = $round($sunDay * 2);
                            $sunHolNight = $round($sunNight * 2);

                            // Ici on ÉCRASE volontairement (car auto-calcul activé)
                            $set('rate_night_hour', $night);
                            $set('rate_sun_day_hour', $sunDay);
                            $set('rate_sun_night_hour', $sunNight);

                            $set('rate_hol_day_hour', $holDay);
                            $set('rate_hol_night_hour', $holNight);

                            $set('rate_sun_hol_day_hour', $sunHolDay);
                            $set('rate_sun_hol_night_hour', $sunHolNight);
                        }),

                    Forms\Components\TextInput::make('rate_night_hour')
                        ->label('Nuit (21h-6h)')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->disabled(fn (callable $get) => (bool) $get('rates_auto')),

                    Forms\Components\TextInput::make('rate_sun_day_hour')
                        ->label('Dimanche jour')
                        ->numeric()
                        ->default(0)
                        ->disabled(fn (callable $get) => (bool) $get('rates_auto')),

                    Forms\Components\TextInput::make('rate_sun_night_hour')
                        ->label('Dimanche nuit')
                        ->numeric()
                        ->default(0)
                        ->disabled(fn (callable $get) => (bool) $get('rates_auto')),

                    Forms\Components\TextInput::make('rate_hol_day_hour')
                        ->label('Férié jour')
                        ->numeric()
                        ->default(0)
                        ->disabled(fn (callable $get) => (bool) $get('rates_auto')),

                    Forms\Components\TextInput::make('rate_hol_night_hour')
                        ->label('Férié nuit')
                        ->numeric()
                        ->default(0)
                        ->disabled(fn (callable $get) => (bool) $get('rates_auto')),

                    Forms\Components\TextInput::make('rate_sun_hol_day_hour')
                        ->label('Dimanche férié jour')
                        ->numeric()
                        ->default(0)
                        ->disabled(fn (callable $get) => (bool) $get('rates_auto')),

                    Forms\Components\TextInput::make('rate_sun_hol_night_hour')
                        ->label('Dimanche férié nuit')
                        ->numeric()
                        ->default(0)
                        ->disabled(fn (callable $get) => (bool) $get('rates_auto')),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code_article_compta')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('unit_type')->sortable(),
                Tables\Columns\TextColumn::make('unit_price_default')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('vat_rate_default')->numeric()->sortable(),
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
        $data['tenant_id'] = optional(Filament::auth()->user())->tenant_id;
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data, $record): array
    {
        $data['tenant_id'] = optional(Filament::auth()->user())->tenant_id;
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
