<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventQueue extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'name', 'slug', 'description', 'starts_at', 'ends_at', 'arrival_date', 'session_label', 'daily_quota',
        'session_quota', 'checkin_grace_minutes', 'status', 'public_link_enabled',
        'mask_participant_names', 'public_token', 'tv_token', 'ticket_prefix', 'reference_prefix',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'arrival_date' => 'date',
            'public_link_enabled' => 'boolean',
            'mask_participant_names' => 'boolean',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventQueueParticipant::class);
    }

    public function isAcceptingRegistrations(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->public_link_enabled;
    }
}
