<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'contact_email',
    ];

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function missions()
    {
        return $this->hasMany(Mission::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function tenantSetting()
    {
        return $this->hasOne(TenantSetting::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}