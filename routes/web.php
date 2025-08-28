<?php

use App\Filament\Pages\QueueStatus;
use Illuminate\Support\Facades\Route;

Route::get('queue-status', QueueStatus::class)->name('queue.status');