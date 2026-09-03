<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesMasterDataCache;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use InvalidatesMasterDataCache;

    protected $table = 'services';

    protected $fillable = [
        'instansi_id', 'name', 'prefix', 'padding', 'counter_id', 'is_active', 'is_accepting_queues', 'is_archived',
        'queue_schedule', 'daily_queue_quota', 'queue_override', 'queue_override_reason', 'queue_override_until',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_accepting_queues' => 'boolean',
            'is_archived' => 'boolean',
            'padding' => 'integer',
            'queue_schedule' => 'array',
            'daily_queue_quota' => 'integer',
            'queue_override_until' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Service $service): void {
            if ($service->padding === null || (int) $service->padding <= 0) {
                $service->padding = 2;
            }
        });

        static::updated(function (Service $service): void {
            if (! $service->wasChanged(['queue_override', 'queue_override_reason', 'queue_override_until'])) {
                return;
            }

            ServiceQueueOverrideLog::create([
                'service_id' => $service->id,
                'user_id' => auth()->id(),
                'action' => $service->queue_override ?: 'clear_override',
                'reason' => $service->queue_override_reason,
                'valid_until' => $service->queue_override_until,
                'snapshot' => [
                    'previous' => $service->getOriginal('queue_override'),
                    'current' => $service->queue_override,
                ],
            ]);
        });
    }

    // Satu layanan dapat dilayani oleh beberapa loket; setiap loket memilih satu layanan.
    public function counters()
    {
        return $this->hasMany(Counter::class, 'service_id');
    }

    // relasi ke Queue
    public function queues()
    {
        return $this->hasMany(Queue::class, 'service_id', 'id');
    }

    public function instansi()
    {
        return $this->belongsTo(Instansi::class, 'instansi_id', 'instansi_id');
    }

    public function queueDateOverrides()
    {
        return $this->hasMany(ServiceQueueDateOverride::class);
    }

    public function queueOverrideLogs()
    {
        return $this->hasMany(ServiceQueueOverrideLog::class);
    }
}
