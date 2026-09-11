<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineQueueSpecialWindow extends Model
{
    public const MODE_HYBRID = 'hybrid';
    public const MODE_ONLINE_ONLY = 'online_only';

    protected $fillable = [
        'service_id', 'date', 'starts_at', 'ends_at', 'mode', 'is_active', 'note', 'created_by',
    ];

    protected function casts(): array
    {
        return ['date' => 'date', 'is_active' => 'boolean'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
