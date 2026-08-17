<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceRegistration extends Model
{
    protected $fillable = [
        'device_key',
        'device_type',
        'zone_number',
        'ip_address',
        'user_agent',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'zone_number' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }
}
