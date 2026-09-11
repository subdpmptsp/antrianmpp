<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineQueueAudit extends Model
{
    public $timestamps = false;

    protected $fillable = ['online_queue_reservation_id', 'user_id', 'action', 'metadata', 'created_at'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(OnlineQueueReservation::class, 'online_queue_reservation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
