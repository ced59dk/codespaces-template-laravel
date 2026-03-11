<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'code_article_compta',
        'unit_type',
        'unit_price_default',
        'vat_rate_default',
        'tenant_id','name','code_article_compta','unit_type',
        'unit_price_default','vat_rate_default',
        'rate_day_hour','rate_night_hour',
        'rate_sun_day_hour','rate_sun_night_hour',
        'rate_hol_day_hour','rate_hol_night_hour',
        'rate_sun_hol_day_hour','rate_sun_hol_night_hour',
    ];

    protected $casts = [
        'unit_price_default' => 'decimal:2',
        'vat_rate_default' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function missions()
    {
        return $this->hasMany(Mission::class);
    }
}