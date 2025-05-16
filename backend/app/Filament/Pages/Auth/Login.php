<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected static string $view = 'filament-panels::pages.auth.login';

    public function getTitle(): string
    {
        return 'Masuk';
    }

    public function getHeading(): string
    {
        return 'Masuk ke akun Anda';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata Sandi')
            ->password()
            ->required()
            ->revealable(false);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('Ingat saya');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Masuk')
            ->submit('authenticate');
    }

    public function registerAction(): Action
    {
        return Action::make('register')
            ->label('Daftar akun baru')
            ->url(filament()->getRegistrationUrl());
    }

    protected function getFooterActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
            $this->registerAction(),
        ];
    }

    protected function onAuthenticated(): void
    {
        Notification::make()
            ->title('Berhasil masuk')
            ->success()
            ->send();
    }

    public function authenticate(): \Filament\Http\Responses\Auth\Contracts\LoginResponse|null
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('Terlalu banyak upaya')
                ->body('Harap tunggu sebelum mencoba kembali.')
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt([
            'email' => $data['email'],
            'password' => $data['password'],
        ], $data['remember'] ?? false)) {
            throw ValidationException::withMessages([
                'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }

        $this->onAuthenticated();

        return app(\Filament\Http\Responses\Auth\Contracts\LoginResponse::class);
    }
}