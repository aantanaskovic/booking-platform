<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::guest')] class extends Component {
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, $this->remember)) {
            $this->addError('email', 'The provided credentials are incorrect.');

            return;
        }

        session()->regenerate();

        $this->redirectIntended(default: route('dashboard'));
    }
};
?>

<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md rounded-xl bg-white p-8 shadow-sm">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">
                Sign in
            </h1>

            <p class="mt-2 text-sm text-gray-600">
                Sign in to your account to continue.
            </p>
        </div>

        <form wire:submit="login" class="space-y-6">
            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-gray-700">
                    Email
                </label>

                <input wire:model.blur="email" type="email" id="email" autocomplete="email"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">

                @error('email')
                    <p class="mt-2 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-gray-700">
                    Password
                </label>

                <input wire:model.blur="password" type="password" id="password" autocomplete="current-password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">

                @error('password')
                    <p class="mt-2 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <label class="flex items-center gap-2">
                <input wire:model="remember" type="checkbox" class="rounded border-gray-300">

                <span class="text-sm text-gray-600">
                    Remember me
                </span>
            </label>

            <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                <span wire:loading.remove>
                    Sign in
                </span>

                <span wire:loading>
                    Signing in...
                </span>
            </button>
        </form>
    </div>
</div>
