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
        'image_size',
        'landing_logo',
        'landing_city_logo',
        'landing_logo_size',
        'landing_city_logo_size',
        'landing_hero_image',
        'landing_hero_image_size',
        'kiosk_logo',
        'kiosk_logo_size',
        'kiosk_office_logo',
        'kiosk_office_logo_size',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => app(MppBrandingService::class)->forget());
        static::deleted(fn () => app(MppBrandingService::class)->forget());
    }
}
