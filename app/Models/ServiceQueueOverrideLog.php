<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceQueueOverrideLog extends Model
{
    protected $fillable = ['service_id', 'user_id', 'action', 'reason', 'valid_until', 'snapshot'];

    protected function casts(): array
    {
        return ['valid_until' => 'datetime', 'snapshot' => 'array'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
