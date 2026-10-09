<?php

use App\Actions\Bookings\ReschedulePublicBooking;
use App\Models\Booking;
use App\Queries\Bookings\AvailableBookingSlots;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component {
    public Booking $booking;

    public bool $rescheduling = false;

    public ?string $selectedDate = null;

    public array $availableSlots = [];

    public ?string $selectedSlot = null;

    public function mount(int $bookingId): void
    {
        $this->booking = Booking::query()
            ->with(['customer', 'service'])
            ->findOrFail($bookingId);

        $customerId = (int) request()->query('customerId');

        abort_if($this->booking->customer_id !== $customerId, 403);
    }

    public function updatedSelectedDate(AvailableBookingSlots $availableBookingSlots): void
    {
        $this->selectedSlot = null;
        $this->availableSlots = [];

        if (!$this->selectedDate) {
            return;
        }

        $slots = $availableBookingSlots->get(user: $this->booking->user, serviceId: $this->booking->service_id, date: Carbon::parse($this->selectedDate), exceptBookingId: $this->booking->id);

        $this->availableSlots = $slots
            ->map(
                fn(array $slot) => [
                    'starts_at' => $slot['starts_at']->toDateTimeString(),
                    'ends_at' => $slot['ends_at']->toDateTimeString(),
                ],
            )
            ->all();
    }

    public function reschedule(ReschedulePublicBooking $reschedulePublicBooking, AvailableBookingSlots $availableBookingSlots): void
    {
        $this->validate([
            'selectedDate' => ['required', 'date', 'after_or_equal:today'],
            'selectedSlot' => ['required', 'date'],
        ]);

        $slots = $availableBookingSlots->get(user: $this->booking->user, serviceId: $this->booking->service_id, date: Carbon::parse($this->selectedDate), exceptBookingId: $this->booking->id);

        $selectedSlot = collect($this->availableSlots)->firstWhere('starts_at', $this->selectedSlot);

        $slotIsAvailable = $slots->contains(fn(array $slot) => $slot['starts_at']->toDateTimeString() === $this->selectedSlot);

        if (!$selectedSlot || !$slotIsAvailable) {
            throw ValidationException::withMessages([
                'selectedSlot' => 'The selected time is no longer available.',
            ]);
        }

        $reschedulePublicBooking->reschedule(customer: $this->booking->customer, bookingId: $this->booking->id, startsAt: Carbon::parse($selectedSlot['starts_at']), endsAt: Carbon::parse($selectedSlot['ends_at']));

        $this->booking->refresh();
        $this->rescheduling = false;
        $this->selectedSlot = null;
        $this->availableSlots = [];

        session()->flash('status', 'Booking rescheduled successfully.');
    }

    public function startRescheduling(): void
    {
        $this->rescheduling = true;

        $this->js(
            <<<'JS'
                setTimeout(() => {
                    document.getElementById('reschedule-section')?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    });
                }, 50);
            JS
            ,
        );
    }
};
?>

<div class="mx-auto max-w-2xl px-4 py-10">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-md">
        <h1 class="text-xl font-semibold text-gray-900">
            Manage your booking
        </h1>

        <p class="mt-2 text-sm text-gray-600">
            Review your booking details.
        </p>

        @if (session('status'))
            <p class="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">
                {{ session('status') }}
            </p>
        @endif

        <div class="mt-6 space-y-4">
            <div>
                <p class="text-sm text-gray-500">Customer</p>
                <p class="font-medium text-gray-900">
                    {{ $booking->customer->name }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Service</p>
                <p class="font-medium text-gray-900">
                    {{ $booking->service->name }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Date and time</p>
                <p class="font-medium text-gray-900">
                    {{ $booking->starts_at->format('M j, Y, H:i') }}
                    –
                    {{ $booking->ends_at->format('H:i') }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Status</p>
                <p class="font-medium text-gray-900">
                    {{ $booking->status->value }}
                </p>
            </div>
        </div>

        @if ($booking->status->canBeCancelled())
            <button type="button" wire:click="startRescheduling"
                class="mt-6 w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700">
                Reschedule booking
            </button>
        @endif

        @if ($rescheduling)
            <div id="reschedule-section" class="mt-6 border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-900">
                    Choose a new date
                </h2>

                <label for="reschedule-date" class="mt-4 block text-sm font-medium text-gray-700">
                    Date
                </label>

                <input id="reschedule-date" type="date" min="{{ now()->toDateString() }}"
                    wire:model.live="selectedDate"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" />

                @error('selectedDate')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror

                <h3 class="mt-5 text-sm font-medium text-gray-700">
                    Choose a new time
                </h3>

                @if (count($availableSlots))
                    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($availableSlots as $slot)
                            <button type="button" wire:key="slot-{{ $slot['starts_at'] }}"
                                wire:click="$set('selectedSlot', '{{ $slot['starts_at'] }}')"
                                @class([
                                    'rounded-lg border px-3 py-2 text-sm font-medium transition',
                                    'border-indigo-600 bg-indigo-50 text-indigo-700' =>
                                        $selectedSlot === $slot['starts_at'],
                                    'border-gray-300 text-gray-700 hover:border-indigo-500 hover:bg-indigo-50' =>
                                        $selectedSlot !== $slot['starts_at'],
                                ])>
                                {{ Carbon::parse($slot['starts_at'])->format('H:i') }}
                            </button>
                        @endforeach
                    </div>
                @elseif ($selectedDate)
                    <p class="mt-3 text-sm text-gray-500">
                        No available times for this date.
                    </p>
                @endif

                @error('selectedSlot')
                    <p class="mt-3 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror

                @if ($selectedSlot)
                    <p class="mt-4 text-sm text-gray-600">
                        Selected time:
                        <span class="font-medium text-gray-900">
                            {{ Carbon::parse($selectedSlot)->format('M j, Y, H:i') }}
                        </span>
                    </p>

                    <button type="button" wire:click="reschedule" wire:loading.attr="disabled" wire:target="reschedule"
                        class="mt-5 w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="reschedule">
                            Confirm new time
                        </span>

                        <span wire:loading wire:target="reschedule">
                            Rescheduling...
                        </span>
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>
