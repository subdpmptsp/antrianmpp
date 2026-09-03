<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounterQueueOverrideLog extends Model
{
    protected $fillable = ['counter_id', 'user_id', 'action', 'reason', 'valid_until', 'snapshot'];

    protected function casts(): array
    {
        return ['valid_until' => 'datetime', 'snapshot' => 'array'];
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class);
    }
}
