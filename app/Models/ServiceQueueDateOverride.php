<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceQueueDateOverride extends Model
{
    protected $fillable = ['service_id', 'date', 'is_closed', 'reason', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date', 'is_closed' => 'boolean'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
