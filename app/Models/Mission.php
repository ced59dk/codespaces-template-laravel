<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_ENDED = 'ended';
    public const STATUS_READY_TO_TRANSMIT = 'ready_to_transmit';
    public const STATUS_TRANSMITTED = 'transmitted';
    public const STATUS_INVOICED_EXTERNAL = 'invoiced_external';
    public const STATUS_ANOMALY = 'anomaly';

    public static array $STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_VALIDATED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_ENDED,
        self::STATUS_READY_TO_TRANSMIT,
        self::STATUS_TRANSMITTED,
        self::STATUS_INVOICED_EXTERNAL,
        self::STATUS_ANOMALY,
    ];

    protected $fillable = [
        'tenant_id',
        'client_id',
        'service_id',
        'reference_commande',
        'objet',
        'start_at',
        'end_at',
        'quantity',
        'unit_price',
        'vat_rate',
        'status',
        'notes',
        'created_by',
        'validated_by',
        'closed_by',
        'transmitted_by',
        'transmitted_at',
        'external_invoice_ref',
        'external_invoiced_at',
        'quantity_manual',
        'quantity_manual_reason',
            // Breakdown minutes + montant
        'min_total',
        'min_day',
        'min_night',
        'min_sun_day',
        'min_sun_night',
        'min_hol_day',
        'min_hol_night',
        'min_sun_hol_day',
        'min_sun_hol_night',
        'amount_ht',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'transmitted_at' => 'datetime',
        'external_invoiced_at' => 'datetime',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'quantity_manual' => 'boolean',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function transmitter()
    {
        return $this->belongsTo(User::class, 'transmitted_by');
    }
}