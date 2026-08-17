<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;

class Login extends BaseLogin
{
    protected function getRedirectUrl(): string
    {
        if (auth()->user()?->can('operate-counter') && auth()->user()?->role === 'operator') {
            return route('filament.admin.pages.dashboard-call-kiosk');
        }

        return parent::getRedirectUrl();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['login'],
            'password' => $data['password'],
        ];
    }
    
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Username')
            ->required()
            ->maxLength(255)
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1])
            ->validationAttribute('username');
    }
}
