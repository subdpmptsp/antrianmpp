<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesMasterDataCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Counter extends Model
{
    use InvalidatesMasterDataCache;

    protected $fillable = [
        'name',
        'instansi_id',
        'service_id',
        'is_active',
    ];

    protected static function booted()
    {
        static::addGlobalScope('roleBasedAccess', function (Builder $builder) {
            if (Auth::user()?->isOperator()) {
                $builder->where('id', Auth::user()->counter_id);
            }
        });
    }

    // Relasi ke tabel Instansi
    public function instansi()
    {
        return $this->belongsTo(Instansi::class, 'instansi_id', 'instansi_id');
    }

    // Relasi 1:1 dengan Service (satu counter = satu service)
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function instansis()
    {
        return $this->hasMany(Instansi::class, 'counter_id', 'id');
    }

    // Relasi ke User (1:1)
    public function user()
    {
        return $this->hasOne(User::class, 'counter_id');
    }

    // Relasi ke tabel Queue
    public function queues()
    {
        return $this->hasMany(Queue::class, 'counter_id');
    }

    // Queue yang aktif (sedang dilayani atau dipanggil)
    public function activeQueue()
    {
        // Pastikan hanya mengambil queue yang sesuai dengan service counter ini
        return $this->hasOne(Queue::class, 'service_id', 'service_id')
            ->whereIn('status', ['called', 'serving'])
            ->whereDate('created_at', now()->toDateString())
            ->orderByRaw("CASE WHEN status = 'serving' THEN 1 WHEN status = 'called' THEN 2 END")
            ->latest('called_at');
    }

    // Queue berikutnya (relasi, supaya bisa eager load dengan with())
    public function nextQueue()
    {
        // Cari queue waiting yang sesuai dengan service counter ini
        // Menggunakan whereColumn untuk match service_id dari counter ke queue
        return $this->hasOne(Queue::class, 'service_id', 'service_id')
            ->where('status', 'waiting')
            ->whereNull('counter_id')
            ->whereNull('called_at')
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('id', 'asc');
    }

    // Apakah loket masih tersedia
    public function getIsAvailableAttribute()
    {
        if ($this->relationLoaded('activeQueue')) {
            return $this->activeQueue === null && $this->is_active;
        }

        $hasActiveQueue = $this->queues()
            ->whereIn('status', ['serving', 'called'])
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        return ! $hasActiveQueue && $this->is_active;
    }

    // Get current serving queue (prioritas: serving > called)
    public function getCurrentQueueAttribute()
    {
        return $this->queues()
            ->whereIn('status', ['serving', 'called'])
            ->whereDate('created_at', now()->toDateString())
            ->orderByRaw("CASE WHEN status = 'serving' THEN 1 WHEN status = 'called' THEN 2 END")
            ->latest('called_at')
            ->first();
    }
}
