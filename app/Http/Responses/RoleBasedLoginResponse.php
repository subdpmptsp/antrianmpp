<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class RoleBasedLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = auth()->user();

        if ($user?->role === 'operator') {
            return redirect()->route('filament.admin.pages.dashboard-call-kiosk');
        }

        return redirect()->route('filament.admin.pages.monitoring-dashboard');
    }
}
