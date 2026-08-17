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
    
    protected $fillable = ['nama_instansi', 'deskripsi', 'counter_id'];

    public function counter()
    {
        return $this->belongsTo(Counter::class, 'counter_id', 'id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'instansi_id', 'instansi_id');
    }
}
