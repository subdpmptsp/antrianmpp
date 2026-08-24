<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Holiday extends Model
{
    protected $fillable = [
        'date',
        'name',
        'type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetAttendanceRecapCache());
        static::deleted(fn () => static::forgetAttendanceRecapCache());
    }

    private static function forgetAttendanceRecapCache(): void
    {
        foreach (range(now()->year - 1, now()->year + 1) as $year) {
            Cache::forget('attendance:monthly-recap:'.$year);
        }
    }
}
