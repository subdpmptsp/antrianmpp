<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesMasterDataCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Counter extends Model
{
    use InvalidatesMasterDataCache;

    protected $fillable = [
        'name',
        'code_loket',
        'instansi_id',
        'service_id',
        'is_active',
        'is_archived',
    ];

    protected static function booted()
    {
        static::addGlobalScope('roleBasedAccess', function (Builder $builder) {
            if (Auth::user()?->isOperator()) {
                $builder->where('id', Auth::user()->counter_id);
            }
        });

        static::saving(function (Counter $counter): void {
            if (! $counter->instansi_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'instansi_id' => 'Loket wajib terhubung ke instansi.',
                ]);
            }

            $instansi = Instansi::query()->find($counter->instansi_id);
            if (! $instansi) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'instansi_id' => 'Instansi yang dipilih tidak ditemukan.',
                ]);
            }

            if ($counter->is_active && ! $counter->is_archived && ! $counter->service_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'service_id' => 'Loket aktif wajib memiliki layanan utama.',
                ]);
            }

            if ($counter->service_id) {
                $serviceBelongsToInstitution = Service::query()
                    ->whereKey($counter->service_id)
                    ->where('instansi_id', $counter->instansi_id)
                    ->exists();

                if (! $serviceBelongsToInstitution) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'service_id' => 'Layanan harus berasal dari instansi yang sama dengan loket.',
                    ]);
                }
            }

            // name dipertahankan untuk kompatibilitas layar lama, tetapi sumber
            // kebenaran zona sekarang berada pada instansi.
            $counter->name = $instansi->zone;
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

    /**
     * Layanan bantuan yang boleh dipanggil dari loket ini. Layanan utama
     * tetap disimpan pada service_id agar struktur lama tetap kompatibel.
     */
    public function additionalServices(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'counter_service')->withTimestamps();
    }

    /** @return Collection<int, int> */
    public function callableServiceIds(): Collection
    {
        return $this->additionalServices()
            ->pluck('services.id')
            ->push($this->service_id)
            ->filter()
            ->map(fn ($serviceId): int => (int) $serviceId)
            ->unique()
            ->values();
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

    public function closureRequests()
    {
        return $this->hasMany(CounterClosureRequest::class);
    }

    /** Pengaturan jadwal khusus untuk loket ini; kosong berarti ikut jadwal global. */
    public function queueScheduleOverride(): HasOne
    {
        return $this->hasOne(CounterQueueScheduleOverride::class);
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

    public function getDisplayNameAttribute(): string
    {
        return $this->code_loket ? 'Loket '.$this->code_loket : $this->name;
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
