<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesMasterDataCache;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use InvalidatesMasterDataCache;

    protected $table = 'services';

    protected $fillable = ['instansi_id', 'name', 'prefix', 'padding', 'counter_id', 'is_active', 'is_accepting_queues'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_accepting_queues' => 'boolean',
            'padding' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Service $service): void {
            if ($service->padding === null || (int) $service->padding <= 0) {
                $service->padding = 2;
            }
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
}
