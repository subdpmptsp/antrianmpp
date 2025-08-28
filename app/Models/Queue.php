<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    
    protected $fillable = [
        'counter_id',
        'service_id',
        'number',
        'status',
        'called_at',
        'served_at',
        'canceled_at',
        'finished_at'
    ];

    public function getKioskLabelAttribute()
    {
        if ($this->status === 'waiting') return "Dipanggil";

        if ($this->status === 'serving') return "Dilayani";

        return '';
    }

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

}
