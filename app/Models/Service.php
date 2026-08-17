<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesMasterDataCache;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use InvalidatesMasterDataCache;

    protected $table = 'services';

    protected $fillable = ['instansi_id', 'name', 'prefix', 'padding', 'counter_id', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'padding' => 'integer',
        ];
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
