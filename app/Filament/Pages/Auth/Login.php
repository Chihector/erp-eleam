<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Correo Electrónico o RUT')
            ->placeholder('ej: admin@eleam.cl o 11111111-1')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Entrar al Sistema')
            ->submit('authenticate');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $loginInput = trim((string) ($data['email'] ?? ''));
        $cleanedRut = preg_replace('/[^0-9kK]/', '', $loginInput);

        // Find user by exact email, exact rut, or sanitized rut
        $user = User::query()
            ->where('email', $loginInput)
            ->orWhere('rut', $loginInput)
            ->when(!empty($cleanedRut), function ($query) use ($cleanedRut) {
                $query->orWhereRaw("REPLACE(REPLACE(rut, '.', ''), '-', '') = ?", [$cleanedRut]);
            })
            ->first();

        return [
            'email' => $user ? $user->email : $loginInput,
            'password' => $data['password'] ?? '',
        ];
    }
}
