<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineQueueReservation extends Model
{
    public const STATUS_BOOKED = 'booked';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'online_queue_session_id', 'service_id', 'service_date', 'booking_code', 'access_token',
        'nik', 'nik_hash', 'nik_last_four', 'identity_verification_status',
        'identity_verification_provider', 'identity_verification_reference', 'identity_verified_at',
        'active_identity_key', 'name', 'phone', 'status',
        'checked_in_at', 'canceled_at', 'expired_at',
    ];

    protected $hidden = ['nik', 'nik_hash', 'active_identity_key', 'access_token'];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'nik' => 'encrypted',
            'identity_verified_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'canceled_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(OnlineQueueSession::class, 'online_queue_session_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function queue(): HasOne
    {
        return $this->hasOne(Queue::class, 'online_queue_reservation_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(OnlineQueueAudit::class, 'online_queue_reservation_id')->latest('created_at');
    }

    public function getMaskedNikAttribute(): string
    {
        return '************'.$this->nik_last_four;
    }
}
