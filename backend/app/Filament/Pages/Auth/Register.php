<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use App\Models\User; // Pastikan path ini sesuai dengan model User Anda
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Registered; // PASTIKAN INI DI-IMPORT DENGAN BENAR
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException as LivewireTooManyRequestsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\Authenticatable; // Untuk type hint jika diperlukan

class Register extends BaseRegister
{
    protected static string $view = 'filament-panels::pages.auth.register';

    protected ?User $newlyCreatedUserForHook = null; 

    public function getTitle(): string|Htmlable
    {
        return 'Registrasi Akun';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Buat Akun Baru Anda';
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Nama Lengkap')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Alamat Email')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(User::class);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata Sandi')
            ->password()
            ->required()
            ->rule(PasswordRule::default()->mixedCase()->numbers())
            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
            ->same('passwordConfirmation')
            ->validationAttribute('kata sandi');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('Konfirmasi Kata Sandi')
            ->password()
            ->required()
            ->dehydrated(false);
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->label('Sudah punya akun? Masuk di sini')
            ->url(filament()->getLoginUrl());
    }

    protected function getFooterActions(): array
    {
        $currentPanel = Filament::getCurrentPanel();
        if ($currentPanel && ($currentPanel->isDefault() || $currentPanel->getId() === 'admin')) {
            return [
                $this->loginAction(),
            ];
        }
        return [];
    }

    protected function beforeRegister(): void
    {
        // Logika sebelum registrasi
    }

    public function register(): ?RegistrationResponse
    {
        Log::info('Memulai proses registrasi kustom.');
        // PENTING: SANGAT DISARANKAN UNTUK MEMERIKSA VERSI LARAVEL & FILAMENT ANDA.
        // Jika error 'getRegisteredUser does not exist' atau error tipe pada event Registered masih ada,
        // kemungkinan besar ada masalah versi atau ini false positive dari linter.

        try {
            if (method_exists($this, 'rateLimit')) {
                 $this->rateLimit(config('filament-panels.auth.registration.rate_limit.max_attempts', 5));
            }
        } catch (LivewireTooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/register.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->danger()
                ->send();
            return null;
        }

        $data = $this->form->getState();
        
        /** @var Authenticatable|null $userForEvent */
        $userForEvent = null;

        DB::transaction(function () use ($data, &$userForEvent) { // Pass $userForEvent by reference
            $authenticatableUserFromHandle = $this->handleRegistration($data);
            $userForEvent = $authenticatableUserFromHandle; // Simpan untuk dikirim ke event

            if ($authenticatableUserFromHandle instanceof User) {
                $this->newlyCreatedUserForHook = $authenticatableUserFromHandle;
                Log::info("User ID {$authenticatableUserFromHandle->id} disimpan ke newlyCreatedUserForHook.");
            } else {
                Log::error('User yang dibuat oleh handleRegistration bukan instance dari App\Models\User. Tipe aktual: ' . ($authenticatableUserFromHandle ? get_class($authenticatableUserFromHandle) : 'null'));
                $this->newlyCreatedUserForHook = null; 
            }

            if (method_exists($this, 'sendEmailVerificationNotification')) {
                $this->sendEmailVerificationNotification($authenticatableUserFromHandle);
            }
            
            // Baris berikut ini secara teknis sudah benar menurut definisi Laravel.
            // Jika Intelephense masih error, ini kemungkinan besar false positive.
            // Pastikan $userForEvent adalah instance dari Authenticatable.
            if ($userForEvent instanceof Authenticatable) {
                 // @phpstan-ignore-next-line // Contoh cara mengabaikan error PHPStan jika ini false positive
                 // @psalm-suppress ArgumentTypeCoercion // Contoh cara mengabaikan error Psalm
                 // Untuk Intelephense, Anda mungkin perlu mencari cara ignore yang spesifik jika perlu,
                 // atau perbarui Intelephense, atau laporkan sebagai bug jika ini salah.
                event(new Registered($userForEvent));
                Log::info("Event Registered dikirim.");
            } else {
                Log::error("Tidak dapat mengirim event Registered karena user tidak valid atau null setelah handleRegistration.");
            }


            $this->afterRegister();

            $this->newlyCreatedUserForHook = null; 
            Log::info("newlyCreatedUserForHook dibersihkan setelah afterRegister.");
        });

        Notification::make()
            ->title(__('filament-panels::pages/auth/register.notifications.completed.title'))
            ->success()
            ->send();
        
        Log::info('Registrasi selesai, mengarahkan ke halaman login.');
        return app(RegistrationResponse::class, ['redirect' => Filament::getLoginUrl()]);
    }

    protected function afterRegister(): void
    {
        $user = $this->newlyCreatedUserForHook; 
        
        Log::info('Memasuki afterRegister (dengan workaround).');
        if ($user instanceof User) {
            Log::info("User ID {$user->id} ({$user->email}) didapatkan dari workaround untuk assign role.");
            $adminRole = Role::where('name', 'admin')->first();

            if ($adminRole) {
                $user->assignRole($adminRole);
                Log::info("Role '{$adminRole->name}' berhasil diberikan kepada user ID: {$user->id}");
            } else {
                Log::warning("Role 'admin' tidak ditemukan. User ID: {$user->id} tidak mendapatkan role default.");
            }
        } else {
            Log::warning("Di afterRegister (dengan workaround): Pengguna tidak ditemukan dari properti sementara atau bukan instance dari App\Models\User.");
            if ($user) {
                Log::info("Tipe data pengguna yang didapat dari newlyCreatedUserForHook: " . get_class($user));
            } else {
                Log::info("Properti newlyCreatedUserForHook bernilai null di afterRegister.");
            }
        }
    }
}