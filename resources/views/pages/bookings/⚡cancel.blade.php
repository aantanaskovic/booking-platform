<?php

use App\Actions\Bookings\CancelPublicBooking;
use App\Models\Booking;
use Livewire\Component;

new class extends Component {
    public int $bookingId;

    public Booking $booking;

    public bool $confirmingCancellation = false;

    public bool $cancelled = false;

    public function mount(int $bookingId): void
    {
        $this->bookingId = $bookingId;

        $this->booking = Booking::query()
            ->with(['customer', 'service'])
            ->findOrFail($bookingId);

        $customerId = (int) request()->query('customerId');

        if ($this->booking->customer_id !== $customerId) {
            abort(403);
        }
    }

    public function cancel(CancelPublicBooking $cancelPublicBooking): void
    {
        $cancelPublicBooking->cancel(customer: $this->booking->customer, bookingId: $this->booking->id);

        $this->booking->refresh();
        $this->cancelled = true;
    }

    public function confirmCancellation(): void
    {
        $this->confirmingCancellation = true;
    }
};
?>

<div class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-md">
            <h1 class="text-2xl font-semibold text-gray-900">
                Manage your booking
            </h1>

            <p class="mt-2 text-sm text-gray-600">
                Review your booking details below.
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Customer</p>
                    <p class="mt-1 text-sm text-gray-900">
                        {{ $booking->customer->name }}
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500">Service</p>
                    <p class="mt-1 text-sm text-gray-900">
                        {{ $booking->service->name }}
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500">Date</p>
                    <p class="mt-1 text-sm text-gray-900">
                        {{ $booking->starts_at->format('F j, Y') }}
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500">Time</p>
                    <p class="mt-1 text-sm text-gray-900">
                        {{ $booking->starts_at->format('g:i A') }}
                        -
                        {{ $booking->ends_at->format('g:i A') }}
                    </p>
                </div>
            </div>

            @if ($cancelled)
                <div class="mt-8 rounded-lg border border-green-200 bg-green-50 p-4">
                    <p class="text-sm font-medium text-green-900">
                        Booking cancelled
                    </p>

                    <p class="mt-1 text-sm text-green-700">
                        Your booking has been cancelled successfully.
                    </p>
                </div>
            @elseif (!$booking->status->canBeCancelled())
                <div class="mt-8 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="text-sm font-medium text-gray-700">
                        This booking can no longer be cancelled.
                    </p>
                </div>
            @elseif (!$confirmingCancellation)
                <div class="mt-8">
                    <button type="button" wire:click="confirmCancellation"
                        class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-red-700">
                        Cancel booking
                    </button>
                </div>
            @else
                <div class="mt-8 rounded-lg border border-red-200 bg-red-50 p-4">
                    <p class="text-sm font-medium text-red-900">
                        Are you sure you want to cancel this booking?
                    </p>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="button" wire:click="cancel"
                            class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-red-700">
                            Confirm cancellation
                        </button>

                        <button type="button" wire:click="$set('confirmingCancellation', false)"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                            Keep booking
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
