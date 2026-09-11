<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineQueueCheckinChallenge extends Model
{
    protected $fillable = ['token_hash', 'station_code', 'online_queue_reservation_id', 'queue_id', 'expires_at', 'completed_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(OnlineQueueReservation::class, 'online_queue_reservation_id');
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }
}
