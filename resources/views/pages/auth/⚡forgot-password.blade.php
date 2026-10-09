<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::guest')] class extends Component {
    public string $email = '';

    public function sendResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink([
            'email' => $this->email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        session()->flash('status', __($status));

        $this->reset('email');
    }
};
?>

<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md rounded-xl bg-white p-8 shadow-sm">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">
                Forgot your password?
            </h1>

            <p class="mt-2 text-sm text-gray-600">
                Enter your email address and we'll send you a link to reset your password.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit="sendResetLink" class="space-y-6">
            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-gray-700">
                    Email
                </label>

                <input wire:model="email" type="email" id="email" autocomplete="email"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">

                @error('email')
                    <p class="mt-2 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                <span wire:loading.remove>
                    Send reset link
                </span>

                <span wire:loading>
                    Sending...
                </span>
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600">
            Remember your password?
            <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">
                Sign in
            </a>
        </p>
    </div>
</div>
