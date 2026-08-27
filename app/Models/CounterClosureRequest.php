<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounterClosureRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REOPENED = 'reopened';

    protected $fillable = [
        'counter_id',
        'service_id',
        'requested_by_user_id',
        'reason',
        'status',
        'admin_note',
        'reviewed_by_user_id',
        'requested_at',
        'reviewed_at',
        'reopened_by_user_id',
        'reopened_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function reopenedBy()
    {
        return $this->belongsTo(User::class, 'reopened_by_user_id');
    }
}
