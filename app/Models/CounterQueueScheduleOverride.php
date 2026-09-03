<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounterQueueScheduleOverride extends Model
{
    protected $fillable = ['counter_id', 'mode', 'weekly_schedule', 'reason', 'valid_until', 'updated_by'];

    protected function casts(): array
    {
        return ['weekly_schedule' => 'array', 'valid_until' => 'datetime'];
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }
}
