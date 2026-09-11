<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineQueueSession extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'service_id', 'day_of_week', 'starts_at', 'ends_at', 'quota',
        'checkin_open_minutes', 'checkin_grace_minutes', 'status',
    ];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer', 'quota' => 'integer', 'checkin_open_minutes' => 'integer', 'checkin_grace_minutes' => 'integer'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(OnlineQueueReservation::class);
    }
}
