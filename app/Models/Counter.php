<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    
    protected $fillable = ['name', 'service_id', 'is_active'];

    protected static function booted()
    {
        static::addGlobalScope('roleBasedAccess', function(Builder $builder) {
            if (auth()->user()->role === 'operator') {
                $builder->where('id', auth()->user()->counter_id);
            }
        });
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function queues()
    {
        return $this->hasMany(Queue::class);
    }

    public function activeQueue()
    {
        return $this->hasOne(Queue::class)->whereIn('status', ['waiting','serving']);
    }

    
    public function getNextQueueAttribute()
    {
        return Queue::where('service_id', $this->service_id)
            ->where('status', 'waiting')
            ->WhereNull('counter_id')
            ->whereNull('called_at')
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->orderBy('id')
            ->first();
    }

    public function getHasNextQueueAttribute() {
        return Queue::where('service_id', $this->service_id)
            ->where('status', 'waiting')
            ->where(function($query){
                $query->where('counter_id', $this->id)
                    ->orWhereNull('counter_id');
            })->exists() 
        && $this->is_available;
    }

    public function getIsAvailableAttribute()
    {
        $hasServingQueue = $this->queues()
                        ->where('status', 'serving')
                        ->exists();

        return !$hasServingQueue && $this->is_active;
    }

}
