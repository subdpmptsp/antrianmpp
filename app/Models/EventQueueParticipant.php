<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventQueueParticipant extends Model
{
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_CHECKED_IN = 'checked_in';

    public const STATUS_SERVING = 'serving';
    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'event_queue_id', 'ticket_number', 'name', 'nik', 'phone', 'qr_token',
        'status', 'checked_in_at', 'canceled_at',
    ];

    protected function casts(): array
    {
        return ['checked_in_at' => 'datetime', 'served_at' => 'datetime', 'canceled_at' => 'datetime'];
    }

    public function eventQueue(): BelongsTo
    {
        return $this->belongsTo(EventQueue::class);
    }

    public function getMaskedNameAttribute(): string
    {
        $name = trim($this->name);
        if ($name === '') {
            return '-';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        return collect($parts)->map(fn (string $part): string => mb_substr($part, 0, 1).'.')->implode(' ');
    }

    public function getReferenceCodeAttribute(): string
    {
        $prefix = $this->eventQueue?->reference_prefix ?: strtoupper((string) $this->eventQueue?->slug);

        return $prefix.$this->ticket_number;
    }
}
