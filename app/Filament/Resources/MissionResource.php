<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MissionResource\Pages;
use App\Models\Client;
use App\Models\Mission;
use App\Models\Service;
use App\Services\MissionTimeBreakdownCalculator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class MissionResource extends Resource
{
    protected static ?string $model = Mission::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    /**
     * Calcule automatiquement la quantité en fonction du service et des dates
     */
    private static function calculateQuantity(?int $serviceId, ?Carbon $startAt, ?Carbon $endAt, ?bool $quantityManual = false): ?float
    {
        if ($quantityManual) {
            return null;
        }

        if (! $serviceId) {
            return null;
        }

        // IMPORTANT: ne jamais charger un service hors tenant
        $tenantId = optional(Filament::auth()->user())->tenant_id;
        if (! $tenantId) {
            return null;
        }

        $service = Service::where('id', $serviceId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $service) {
            return null;
        }

        $unitType = $service->unit_type;

        if ($unitType === 'fixed') {
            return 1.0;
        }

        if (! $startAt || ! $endAt) {
            return null;
        }

        // Fin avant début => incohérent
        if ($endAt->lessThan($startAt)) {
            return null; // ou 0.0 si tu préfères, mais null est plus parlant
        }

        if ($unitType === 'hour') {
            $minutesDiff = $startAt->diffInMinutes($endAt);
            $quarters = (int) ceil($minutesDiff / 15);
            return round($quarters * 0.25, 2);
        }

        if ($unitType === 'day') {
            $startDate = $startAt->copy()->startOfDay();
            $endDate = $endAt->copy()->startOfDay();
            $daysDiff = $startDate->diffInDays($endDate);
            return (float) ($daysDiff + 1);
        }

        return null;
    }

    private static function minToHours(int $minutes): float
    {
        return round($minutes / 60, 2); // affichage heures décimales
    }

    private static function recalcBreakdown(callable $set, callable $get): void
    {
        $tenantId = optional(Filament::auth()->user())->tenant_id;
        if (! $tenantId) {
            return;
        }

        $serviceId = $get('service_id');
        $startRaw = $get('start_at');
        $endRaw = $get('end_at');

        if (! $serviceId || ! $startRaw || ! $endRaw) {
            // reset si incomplet
            foreach ([
                'min_total','min_day','min_night','min_sun_day','min_sun_night','min_hol_day','min_hol_night','min_sun_hol_day','min_sun_hol_night'
            ] as $k) {
                $set($k, 0);
            }
            $set('amount_ht', 0);
            $set('quantity', null);
            return;
        }

        $start = Carbon::parse($startRaw);
        $end = Carbon::parse($endRaw);

        if ($end->lessThanOrEqualTo($start)) {
            // incohérent
            $set('quantity', null);
            return;
        }

        $service = Service::where('id', $serviceId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $service) {
            return;
        }

        $calc = new MissionTimeBreakdownCalculator();
        $b = $calc->breakdown($start, $end, $service);

        // écrire en base (minutes + montant)
        foreach ($b as $k => $v) {
            $set($k, $v);
        }

        // quantity = heures totales décimales (pour rester compatible avec ton existant)
        $set('quantity', self::minToHours((int) ($b['min_total'] ?? 0)));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->label('Client')
                    ->required()
                    ->searchable()
                    ->options(function () {
                            $tenantId = optional(Filament::auth()->user())->tenant_id;
                        if (! $tenantId) {
                            return [];
                        }
                        return Client::where('tenant_id', $tenantId)->pluck('name', 'id')->toArray();
                    }),
                Forms\Components\Select::make('service_id')
                    ->label('Service')
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $tenantId = optional(Filament::auth()->user())->tenant_id;
                        if (! $state || ! $tenantId) {
                            return;
                        }
                        $service = Service::where('id', $state)->where('tenant_id', $tenantId)->first();
                        if (! $service) {
                            return;
                        }

                        // Ne pré-remplit que si l'utilisateur n'a pas déjà saisi la valeur
                        if (blank($get('unit_price'))) {
                            $set('unit_price', $service->unit_price_default);
                        }

                        if (blank($get('vat_rate'))) {
                            $set('vat_rate', $service->vat_rate_default);
                        }

                        // Recalculer la quantité si manual = false
                        if (! $get('quantity_manual')) {
                            $startAt = $get('start_at') ? new Carbon($get('start_at')) : null;
                            $endAt = $get('end_at') ? new Carbon($get('end_at')) : null;
                            $calculatedQuantity = self::calculateQuantity($state, $startAt, $endAt, false);
                            if ($calculatedQuantity !== null) {
                                $set('quantity', $calculatedQuantity);
                            }
                        }
                    })
                    ->options(function () {
                            $tenantId = optional(Filament::auth()->user())->tenant_id;
                        if (! $tenantId) {
                            return [];
                        }
                        return Service::where('tenant_id', $tenantId)->pluck('name', 'id')->toArray();
                    }),
                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => optional(Filament::auth()->user())->tenant_id),
                Forms\Components\Hidden::make('min_total')->default(0)->dehydrated(true),
                Forms\Components\Hidden::make('min_day')->default(0)->dehydrated(true),
                Forms\Components\Hidden::make('min_night')->default(0)->dehydrated(true),
                Forms\Components\Hidden::make('min_sun_day')->default(0)->dehydrated(true),
                Forms\Components\Hidden::make('min_sun_night')->default(0)->dehydrated(true),
                Forms\Components\Hidden::make('min_hol_day')->default(0)->dehydrated(true),
                Forms\Components\Hidden::make('min_hol_night')->default(0)->dehydrated(true),
                Forms\Components\Hidden::make('min_sun_hol_day')->default(0)->dehydrated(true),
                Forms\Components\Hidden::make('min_sun_hol_night')->default(0)->dehydrated(true),
                Forms\Components\Hidden::make('amount_ht')->default(0)->dehydrated(true),
                Forms\Components\TextInput::make('reference_commande')->label('Référence commande'),
                Forms\Components\TextInput::make('objet')->label('Objet')->required()->maxLength(255),
                Forms\Components\DateTimePicker::make('start_at')
                    ->label('Début')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // Recalculer la quantité si manual = false
                        if (! $get('quantity_manual')) {
                            self::recalcBreakdown($set, $get);
                        }
                    }),
                Forms\Components\DateTimePicker::make('end_at')
                    ->label('Fin')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // Recalculer la quantité si manual = false
                        if (! $get('quantity_manual')) {
                            self::recalcBreakdown($set, $get);
                        }
                    }),
                Forms\Components\Section::make('Heures par tranche')
                    ->schema([
                        Forms\Components\Placeholder::make('h_day')->label('Jour')
                            ->content(fn (callable $get) => self::minToHours((int) ($get('min_day') ?? 0))),
                        Forms\Components\Placeholder::make('h_night')->label('Nuit (21h-6h)')
                            ->content(fn (callable $get) => self::minToHours((int) ($get('min_night') ?? 0))),

                        Forms\Components\Placeholder::make('h_sun_day')->label('Dimanche jour')
                            ->content(fn (callable $get) => self::minToHours((int) ($get('min_sun_day') ?? 0))),
                        Forms\Components\Placeholder::make('h_sun_night')->label('Dimanche nuit')
                            ->content(fn (callable $get) => self::minToHours((int) ($get('min_sun_night') ?? 0))),

                        Forms\Components\Placeholder::make('h_hol_day')->label('Férié jour')
                            ->content(fn (callable $get) => self::minToHours((int) ($get('min_hol_day') ?? 0))),
                        Forms\Components\Placeholder::make('h_hol_night')->label('Férié nuit')
                            ->content(fn (callable $get) => self::minToHours((int) ($get('min_hol_night') ?? 0))),

                        Forms\Components\Placeholder::make('h_sun_hol_day')->label('Dimanche férié jour')
                            ->content(fn (callable $get) => self::minToHours((int) ($get('min_sun_hol_day') ?? 0))),
                        Forms\Components\Placeholder::make('h_sun_hol_night')->label('Dimanche férié nuit')
                            ->content(fn (callable $get) => self::minToHours((int) ($get('min_sun_hol_night') ?? 0))),

                        Forms\Components\Placeholder::make('h_total')->label('Total (h décimales)')
                            ->content(fn (callable $get) => self::minToHours((int) ($get('min_total') ?? 0))),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantité')
                    ->numeric()
                    ->reactive()
                    ->disabled(fn (callable $get) => ! $get('quantity_manual'))
                    ->dehydrated(true)
                    ->placeholder(fn (callable $get) => $get('quantity_manual') ? 'Entrez la quantité' : 'Calculée automatiquement'),
                Forms\Components\Toggle::make('quantity_manual')
                    ->label('Quantité manuelle')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // Si on passe de manual=true à manual=false, recalculer
                        if (! $state) {
                            self::recalcBreakdown($set, $get);
                        }
                    }),
                Forms\Components\Textarea::make('quantity_manual_reason')
                    ->label('Raison de la quantité manuelle')
                    ->visible(fn (callable $get) => $get('quantity_manual'))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('unit_price')->label('Prix unitaire')->numeric(),
                Forms\Components\TextInput::make('vat_rate')->label('TVA (%)')->numeric(),

                Forms\Components\Section::make('recap')
                    ->label('Récap')
                    ->schema([
                        Forms\Components\Placeholder::make('amount_ht_display')
                            ->label('Montant HT (selon tranches)')
                            ->reactive()
                            ->content(fn (callable $get) => number_format((float) ($get('amount_ht') ?? 0), 2, ',', ' ')),

                        Forms\Components\Placeholder::make('total_ttc')
                            ->label('Total TTC')
                            ->reactive()
                            ->content(fn (callable $get) => number_format(((floatval($get('quantity') ?? 0) * floatval($get('unit_price') ?? 0)) * (1 + (floatval($get('vat_rate') ?? 0) / 100))), 2, ',', ' ')),
                    ])->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options(array_combine(Mission::$STATUSES, Mission::$STATUSES))
                    ->default(Mission::STATUS_DRAFT)
                    ->required(),
                Forms\Components\Textarea::make('notes')->label('Notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')->label('Client')->searchable(),
                Tables\Columns\TextColumn::make('service.name')->label('Service')->searchable(),
                Tables\Columns\TextColumn::make('reference_commande')->searchable(),
                Tables\Columns\TextColumn::make('objet')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('start_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('end_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('quantity')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('unit_price')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('vat_rate')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('status')->sortable()->searchable(),
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

        // default unit_price and vat_rate from service if not provided
        if (empty($data['unit_price']) && ! empty($data['service_id'])) {
            $service = Service::where('id', $data['service_id'])->where('tenant_id', $tenantId)->first();
            if ($service) {
                $data['unit_price'] = $service->unit_price_default;
            }
        }

        if (empty($data['vat_rate']) && ! empty($data['service_id'])) {
            $service = Service::where('id', $data['service_id'])->where('tenant_id', $tenantId)->first();
            if ($service) {
                $data['vat_rate'] = $service->vat_rate_default;
            }
        }

        // Calculer la quantité côté serveur si la quantité n'est pas manuelle
        if (! ($data['quantity_manual'] ?? false)) {
            if (! empty($data['service_id']) && ! empty($data['start_at']) && ! empty($data['end_at'])) {

                $service = Service::where('id', $data['service_id'])
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($service) {
                    $start = Carbon::parse($data['start_at']);
                    $end = Carbon::parse($data['end_at']);

                    $calc = new MissionTimeBreakdownCalculator();
                    $b = $calc->breakdown($start, $end, $service);

                    // merge minutes + montant
                    foreach ($b as $k => $v) {
                        $data[$k] = $v;
                    }

                    // quantity = heures décimales totales
                    $data['quantity'] = round(((int) ($b['min_total'] ?? 0)) / 60, 2);
                }
            }
        }

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $tenantId = optional(Filament::auth()->user())->tenant_id;
        $data['tenant_id'] = $tenantId;

        if (empty($data['unit_price']) && ! empty($data['service_id'])) {
            $service = Service::where('id', $data['service_id'])->where('tenant_id', $tenantId)->first();
            if ($service) {
                $data['unit_price'] = $service->unit_price_default;
            }
        }

        if (empty($data['vat_rate']) && ! empty($data['service_id'])) {
            $service = Service::where('id', $data['service_id'])->where('tenant_id', $tenantId)->first();
            if ($service) {
                $data['vat_rate'] = $service->vat_rate_default;
            }
        }

        // Calculer la quantité côté serveur si la quantité n'est pas manuelle
        if (! ($data['quantity_manual'] ?? false)) {
            if (! empty($data['service_id']) && ! empty($data['start_at']) && ! empty($data['end_at'])) {

                $service = Service::where('id', $data['service_id'])
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($service) {
                    $start = Carbon::parse($data['start_at']);
                    $end = Carbon::parse($data['end_at']);

                    $calc = new MissionTimeBreakdownCalculator();
                    $b = $calc->breakdown($start, $end, $service);

                    // merge minutes + montant
                    foreach ($b as $k => $v) {
                        $data[$k] = $v;
                    }

                    // quantity = heures décimales totales
                    $data['quantity'] = round(((int) ($b['min_total'] ?? 0)) / 60, 2);
                }
            }
        }

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
            'index' => Pages\ListMissions::route('/'),
            'create' => Pages\CreateMission::route('/create'),
            'edit' => Pages\EditMission::route('/{record}/edit'),
        ];
    }
}
