<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalIntegrationHealthCheck extends Model
{
    protected $fillable = [
        'integration', 'successful', 'http_status', 'duration_ms',
        'error_code', 'message', 'checked_by',
    ];

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
            'http_status' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
