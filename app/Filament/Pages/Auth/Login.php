<?php

namespace App\Filament\Pages\Auth;

use App\Http\Responses\RoleBasedLoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        if ($response === null) {
            return null;
        }

        // Jangan gunakan redirect intended bawaan Filament, karena URL loket
        // yang tersimpan di sesi dapat mengarahkan admin ke workspace petugas.
        return app(RoleBasedLoginResponse::class);
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
