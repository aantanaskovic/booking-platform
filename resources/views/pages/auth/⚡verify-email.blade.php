<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::guest')] class extends Component {
    public function resend(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectRoute('dashboard');

            return;
        }

        $user->sendEmailVerificationNotification();

        session()->flash('status', 'A new verification link has been sent to your email address.');
    }
};
?>

<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-md rounded-xl bg-white p-8 text-center shadow-sm">
        <h1 class="text-2xl font-bold text-gray-900">
            Verify your email
        </h1>

        <p class="mt-3 text-sm text-gray-600">
            We've sent a verification link to your email address.
            Please check your inbox and click the link to continue.
        </p>

        @if (session('status'))
            <p class="mt-4 text-sm text-green-600">
                {{ session('status') }}
            </p>
        @endif

        <button type="button" wire:click="resend" wire:loading.attr="disabled"
            class="mt-6 rounded-lg bg-indigo-600 px-4 py-2.5 font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
            <span wire:loading.remove>
                Resend verification email
            </span>

            <span wire:loading>
                Sending...
            </span>
        </button>
    </div>
</div>
