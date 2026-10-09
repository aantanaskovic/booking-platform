<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::guest')] class extends Component {
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $credentials['password_confirmation'] = $this->password_confirmation;
        $credentials['token'] = $this->token;

        $status = Password::reset($credentials, function ($user, string $password) {
            $user
                ->forceFill([
                    'password' => $password,
                ])
                ->setRememberToken(str()->random(60));

            $user->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        session()->flash('status', __($status));

        $this->redirectRoute('login');
    }
};
?>

<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">

        <div class="mb-8 text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">
                Reset your password
            </h1>

            <p class="mt-2 text-sm leading-6 text-gray-600">
                Enter your new password below.
            </p>
        </div>

        <form wire:submit="resetPassword" class="space-y-5">

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">
                    Email
                </label>

                <input id="email" type="email" wire:model="email" readonly autocomplete="email"
                    class="w-full cursor-not-allowed rounded-lg border border-gray-300 px-4 py-2.5 bg-gray-50 text-gray-600 shadow-sm focus:border-gray-300 focus:ring-0">

                @error('email')
                    <p class="mt-1.5 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">
                    New password
                </label>

                <input id="password" type="password" wire:model="password" autocomplete="new-password" autofocus
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">

                @error('password')
                    <p class="mt-1.5 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                    Confirm new password
                </label>

                <input id="password_confirmation" type="password" wire:model="password_confirmation"
                    autocomplete="new-password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">

                @error('password_confirmation')
                    <p class="mt-1.5 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <button type="submit"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-medium text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Reset password
            </button>

        </form>
    </div>
</div>
