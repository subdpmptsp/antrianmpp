<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineQueueSetting extends Model
{
    protected $fillable = [
        'global_enabled', 'regular_enabled', 'event_enabled', 'booking_window_days',
        'pilot_mode', 'pilot_service_ids',
    ];

    protected function casts(): array
    {
        return [
            'global_enabled' => 'boolean',
            'regular_enabled' => 'boolean',
            'event_enabled' => 'boolean',
            'pilot_mode' => 'boolean',
            'pilot_service_ids' => 'array',
            'booking_window_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'global_enabled' => false,
            'regular_enabled' => false,
            'event_enabled' => false,
            'pilot_mode' => true,
            'pilot_service_ids' => [],
            'booking_window_days' => 7,
        ]);
    }
}
