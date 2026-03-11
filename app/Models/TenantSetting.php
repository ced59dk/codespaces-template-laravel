<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantSetting extends Model
{
    use HasFactory;

    protected $table = 'tenant_settings';

    protected $fillable = [
        'tenant_id',
        'accounting_emails',
        'rounding_rule',
        'csv_mapping',
    ];

    protected $casts = [
        'accounting_emails' => 'array',
        'csv_mapping' => 'array',
    ];

    protected $attributes = [
        'rounding_rule' => 'quarter_hour',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}