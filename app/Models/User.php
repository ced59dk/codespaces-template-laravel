<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_OPS = 'ops';
    public const ROLE_ACCOUNTANT = 'accountant';
    public const ROLE_READONLY = 'readonly';

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'role',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

   public function missionsTransmitted()
    {
        return $this->hasMany(Mission::class, 'transmitted_by');
    }

    public function missionsCreated()
    {
        return $this->hasMany(Mission::class, 'created_by');
    }

    public function missionsValidated()
    {
        return $this->hasMany(Mission::class, 'validated_by');
    }

    public function missionsClosed()
    {
        return $this->hasMany(Mission::class, 'closed_by');
    }
}