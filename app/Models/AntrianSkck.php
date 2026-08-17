<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AntrianSkck extends Model
{
    protected $guarded = [];

    public function getMaskedNameAttribute(): string
    {
        return Str::of((string) $this->nama)
            ->squish()
            ->explode(' ')
            ->filter()
            ->map(function (string $part): string {
                $length = mb_strlen($part);

                return mb_strtoupper(mb_substr($part, 0, 1)) . str_repeat('*', max($length - 1, 2));
            })
            ->implode(' ');
    }
}
