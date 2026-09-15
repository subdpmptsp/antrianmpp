<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueOperatingSetting extends Model
{
    protected $fillable = ['weekly_schedule', 'cutoff_minutes', 'default_daily_quota', 'kiosk_messages'];

    protected function casts(): array
    {
        return ['weekly_schedule' => 'array', 'cutoff_minutes' => 'integer', 'default_daily_quota' => 'integer', 'kiosk_messages' => 'array'];
    }
}
