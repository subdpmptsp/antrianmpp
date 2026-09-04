<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesMasterDataCache;
use Illuminate\Database\Eloquent\Model;

class Instansi extends Model
{
    use InvalidatesMasterDataCache;

    protected $table = 'instansis';

    protected $primaryKey = 'instansi_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'nama_instansi', 'zone', 'is_active', 'is_archived',
        'work_days_per_week', 'deskripsi', 'logo_path',
    ];

    protected function casts(): array
    {
        return [
            'work_days_per_week' => 'integer',
            'is_active' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    public function counters()
    {
        return $this->hasMany(Counter::class, 'instansi_id', 'instansi_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'instansi_id', 'instansi_id');
    }

    public function getZoneNumberAttribute(): ?int
    {
        $zoneId = collect(config('tv.zones', []))
            ->search(fn (array $zone): bool => mb_strtoupper((string) ($zone['name'] ?? '')) === mb_strtoupper($this->zone));

        return $zoneId === false ? null : (int) $zoneId;
    }
}
