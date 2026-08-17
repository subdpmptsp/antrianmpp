<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    public const STATUS_WAITING = 'waiting';

    public const STATUS_PRINTING = 'printing';

    public const STATUS_CALLED = 'called';

    public const STATUS_SERVING = 'serving';

    public const STATUS_FINISHED = 'finished';

    public const STATUS_CANCELED = 'canceled';

    public const VALID_STATUSES = [
        self::STATUS_WAITING,
        self::STATUS_PRINTING,
        self::STATUS_CALLED,
        self::STATUS_SERVING,
        self::STATUS_FINISHED,
        self::STATUS_CANCELED,
    ];

    public const ACTIVE_STATUSES = [
        self::STATUS_CALLED,
        self::STATUS_SERVING,
    ];

    protected $fillable = [
        'counter_id',
        'service_id',
        'number',
        'status',
        'called_at',
        'served_at',
        'canceled_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'called_at' => 'datetime',
            'served_at' => 'datetime',
            'canceled_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function getKioskLabelAttribute()
    {
        if ($this->status === self::STATUS_CALLED) {
            return 'Dipanggil';
        }

        if ($this->status === self::STATUS_SERVING) {
            return 'Dilayani';
        }

        return '';
    }

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }
}
