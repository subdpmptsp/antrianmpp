<?php

namespace App\Models;

use App\Services\MppBrandingService;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'image',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => app(MppBrandingService::class)->forget());
        static::deleted(fn () => app(MppBrandingService::class)->forget());
    }
}
