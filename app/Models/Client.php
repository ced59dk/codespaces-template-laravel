<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'code_compta',
        'email',
        'phone',
        'address',
        'vat_number',
    ];

    protected $casts = [
        //
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