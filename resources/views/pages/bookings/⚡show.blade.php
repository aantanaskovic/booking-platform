<?php

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\CompleteBooking;
use App\Actions\Bookings\ConfirmBooking;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Livewire\Component;

new class extends Component {
    public Booking $booking;

    public function mount(int $bookingId): void
    {
        $this->booking = auth()
            ->user()
            ->bookings()
            ->with(['customer', 'service'])
            ->findOrFail($bookingId);
    }

    public function confirm(ConfirmBooking $confirmBooking): void
    {
        $confirmBooking->confirm(user: auth()->user(), bookingId: $this->booking->id);

        $this->booking->refresh();
    }

    public function cancel(CancelBooking $cancelBooking): void
    {
        $cancelBooking->cancel(user: auth()->user(), bookingId: $this->booking->id);

        $this->booking->refresh();
    }

    public function complete(CompleteBooking $completeBooking): void
    {
        $completeBooking->complete(user: auth()->user(), bookingId: $this->booking->id);

        $this->booking->refresh();
    }
}; ?>

<div class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="mb-8">
            <a href="{{ route('bookings.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                ← Back to bookings
            </a>

            <div class="mt-6 flex items-center justify-between">
                <a href="{{ route('bookings.edit', [
                    'bookingId' => $booking->id,
                    'returnRoute' => 'bookings.show',
                ]) }}"
                    data-booking-action="edit"
                    class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Edit booking
                </a>

                <div class="flex items-center gap-3">
                    <div class="flex flex-wrap gap-3">
                        @if ($booking->status === BookingStatus::PENDING)
                            <button type="button" data-booking-action="confirm" wire:click="confirm"
                                class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                                Confirm
                            </button>

                            <button type="button" data-booking-action="cancel" wire:click="cancel"
                                class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Cancel
                            </button>
                        @elseif ($booking->status === BookingStatus::CONFIRMED)
                            <button type="button" data-booking-action="complete" wire:click="complete"
                                class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                                Complete
                            </button>

                            <button type="button" data-booking-action="cancel" wire:click="cancel"
                                class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Cancel
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                        Booking #{{ $booking->id }}
                    </h1>

                    <p class="mt-2 text-sm text-gray-600">
                        {{ $booking->starts_at->format('F j, Y') }}
                        ·
                        {{ $booking->starts_at->format('H:i') }}
                        –
                        {{ $booking->ends_at->format('H:i') }}
                    </p>
                </div>

                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
                    {{ ucfirst($booking->status->value) }}
                </span>
            </div>
        </div>

        <div class="space-y-6">

            <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    Customer
                </h2>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Name
                        </p>

                        <p class="mt-1 text-sm text-gray-900">
                            {{ $booking->customer->name }}
                        </p>
                    </div>

                    @if ($booking->customer->email)
                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                Email
                            </p>

                            <p class="mt-1 text-sm text-gray-900">
                                {{ $booking->customer->email }}
                            </p>
                        </div>
                    @endif

                    @if ($booking->customer->phone)
                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                Phone
                            </p>

                            <p class="mt-1 text-sm text-gray-900">
                                {{ $booking->customer->phone }}
                            </p>
                        </div>
                    @endif
                </div>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    Service
                </h2>

                <div class="mt-5 grid gap-5 sm:grid-cols-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Service
                        </p>

                        <p class="mt-1 text-sm text-gray-900">
                            {{ $booking->service->name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Duration
                        </p>

                        <p class="mt-1 text-sm text-gray-900">
                            {{ $booking->service->duration }} minutes
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Price
                        </p>

                        <p class="mt-1 text-sm text-gray-900">
                            €{{ number_format($booking->service->price, 2) }}
                        </p>
                    </div>
                </div>
            </section>

            @if ($booking->notes)
                <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Notes
                    </h2>

                    <p class="mt-4 whitespace-pre-line text-sm text-gray-700">
                        {{ $booking->notes }}
                    </p>
                </section>
            @endif

        </div>
    </div>
</div>
