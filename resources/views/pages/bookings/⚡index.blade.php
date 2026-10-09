<?php

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\CompleteBooking;
use App\Actions\Bookings\ConfirmBooking;
use App\Enums\BookingDateFilter;
use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Queries\Bookings\BookingsQuery;
use Livewire\Component;

new class extends Component {
    public $bookings;

    public ?BookingStatus $statusFilter = null;

    public ?BookingDateFilter $dateFilter = null;

    public function mount(BookingsQuery $bookingsQuery): void
    {
        $this->refreshBookings($bookingsQuery);
    }

    public function updatedStatusFilter(BookingsQuery $bookingsQuery): void
    {
        $this->refreshBookings($bookingsQuery);
    }

    public function updatedDateFilter(BookingsQuery $bookingsQuery): void
    {
        $this->refreshBookings($bookingsQuery);
    }

    public function filterByStatus(?BookingStatus $status, BookingsQuery $bookingsQuery): void
    {
        $this->statusFilter = $status;

        $this->refreshBookings($bookingsQuery);
    }

    public function confirm(int $bookingId, ConfirmBooking $confirmBooking, BookingsQuery $bookingsQuery): void
    {
        try {
            $confirmBooking->confirm(user: auth()->user(), bookingId: $bookingId);
        } catch (BookingException $exception) {
            $this->addError('booking', $exception->getMessage());

            return;
        }

        $this->refreshBookings($bookingsQuery);
    }

    public function cancel(int $bookingId, CancelBooking $cancelBooking, BookingsQuery $bookingsQuery): void
    {
        try {
            $cancelBooking->cancel(user: auth()->user(), bookingId: $bookingId);
        } catch (BookingException $exception) {
            $this->addError('booking', $exception->getMessage());

            return;
        }

        $this->refreshBookings($bookingsQuery);
    }

    public function complete(int $bookingId, CompleteBooking $completeBooking, BookingsQuery $bookingsQuery): void
    {
        try {
            $completeBooking->complete(user: auth()->user(), bookingId: $bookingId);
        } catch (BookingException $exception) {
            $this->addError('booking', $exception->getMessage());

            return;
        }

        $this->refreshBookings($bookingsQuery);
    }

    private function refreshBookings(BookingsQuery $bookingsQuery): void
    {
        $this->bookings = $bookingsQuery->get(user: auth()->user(), status: $this->statusFilter, dateFilter: $this->dateFilter);
    }
};
?>

<div class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                    Bookings
                </h1>

                <p class="mt-2 text-sm text-gray-600">
                    Manage your bookings.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('bookings.calendar') }}"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                    Calendar
                </a>

                <a href="{{ route('bookings.create', [
                    'returnRoute' => 'bookings.index',
                ]) }}"
                    class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800">
                    New booking
                </a>
            </div>
        </div>

        <div class="mb-6 space-y-4">
            <div>
                <p class="mb-2 text-sm font-medium text-gray-700">Status</p>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="$set('statusFilter', null)" data-filter="all"
                        aria-pressed="{{ $statusFilter === null ? 'true' : 'false' }}" @class([
                            'rounded-md px-3 py-2 text-sm font-medium transition',
                            'bg-gray-900 text-white' => $statusFilter === null,
                            'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' =>
                                $statusFilter !== null,
                        ])>
                        All
                    </button>

                    <button type="button" wire:click="$set('statusFilter', 'pending')" data-filter="pending"
                        aria-pressed="{{ $statusFilter === BookingStatus::PENDING ? 'true' : 'false' }}"
                        @class([
                            'rounded-md px-3 py-2 text-sm font-medium transition',
                            'bg-gray-900 text-white' => $statusFilter === BookingStatus::PENDING,
                            'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' =>
                                $statusFilter !== BookingStatus::PENDING,
                        ])>
                        Pending
                    </button>

                    <button type="button" wire:click="$set('statusFilter', 'confirmed')" data-filter="confirmed"
                        aria-pressed="{{ $statusFilter === BookingStatus::CONFIRMED ? 'true' : 'false' }}"
                        @class([
                            'rounded-md px-3 py-2 text-sm font-medium transition',
                            'bg-gray-900 text-white' => $statusFilter === BookingStatus::CONFIRMED,
                            'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' =>
                                $statusFilter !== BookingStatus::CONFIRMED,
                        ])>
                        Confirmed
                    </button>

                    <button type="button" wire:click="$set('statusFilter', 'cancelled')" data-filter="cancelled"
                        aria-pressed="{{ $statusFilter === BookingStatus::CANCELLED ? 'true' : 'false' }}"
                        @class([
                            'rounded-md px-3 py-2 text-sm font-medium transition',
                            'bg-gray-900 text-white' => $statusFilter === BookingStatus::CANCELLED,
                            'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' =>
                                $statusFilter !== BookingStatus::CANCELLED,
                        ])>
                        Cancelled
                    </button>

                    <button type="button" wire:click="$set('statusFilter', 'completed')" data-filter="completed"
                        aria-pressed="{{ $statusFilter === BookingStatus::COMPLETED ? 'true' : 'false' }}"
                        @class([
                            'rounded-md px-3 py-2 text-sm font-medium transition',
                            'bg-gray-900 text-white' => $statusFilter === BookingStatus::COMPLETED,
                            'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' =>
                                $statusFilter !== BookingStatus::COMPLETED,
                        ])>
                        Completed
                    </button>
                </div>
            </div>

            <div>
                <p class="mb-2 text-sm font-medium text-gray-700">Date</p>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="$set('dateFilter', null)" data-date-filter="all"
                        aria-pressed="{{ $dateFilter === null ? 'true' : 'false' }}" @class([
                            'rounded-md px-3 py-2 text-sm font-medium transition',
                            'bg-gray-900 text-white' => $dateFilter === null,
                            'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' =>
                                $dateFilter !== null,
                        ])>
                        All
                    </button>

                    <button type="button" wire:click="$set('dateFilter', 'today')" data-date-filter="today"
                        aria-pressed="{{ $dateFilter === BookingDateFilter::TODAY ? 'true' : 'false' }}"
                        @class([
                            'rounded-md px-3 py-2 text-sm font-medium transition',
                            'bg-gray-900 text-white' => $dateFilter === BookingDateFilter::TODAY,
                            'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' =>
                                $dateFilter !== BookingDateFilter::TODAY,
                        ])>
                        Today
                    </button>

                    <button type="button" wire:click="$set('dateFilter', 'upcoming')" data-date-filter="upcoming"
                        aria-pressed="{{ $dateFilter === BookingDateFilter::UPCOMING ? 'true' : 'false' }}"
                        @class([
                            'rounded-md px-3 py-2 text-sm font-medium transition',
                            'bg-gray-900 text-white' => $dateFilter === BookingDateFilter::UPCOMING,
                            'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' =>
                                $dateFilter !== BookingDateFilter::UPCOMING,
                        ])>
                        Upcoming
                    </button>

                    <button type="button" wire:click="$set('dateFilter', 'past')" data-date-filter="past"
                        aria-pressed="{{ $dateFilter === BookingDateFilter::PAST ? 'true' : 'false' }}"
                        @class([
                            'rounded-md px-3 py-2 text-sm font-medium transition',
                            'bg-gray-900 text-white' => $dateFilter === BookingDateFilter::PAST,
                            'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50' =>
                                $dateFilter !== BookingDateFilter::PAST,
                        ])>
                        Past
                    </button>
                </div>
            </div>
        </div>

        @error('booking')
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $message }}
            </div>
        @enderror

        @if ($bookings->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <h2 class="text-lg font-semibold text-gray-900">
                    No bookings yet.
                </h2>

                <p class="mt-2 text-sm text-gray-500">
                    Create your first booking to get started.
                </p>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Date & Time
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Customer
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Service
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Price
                                </th>

                                <th
                                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Status
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($bookings as $booking)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        {{ $booking->starts_at->format('d.m.Y. H:i') }}
                                        –
                                        {{ $booking->ends_at->format('H:i') }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $booking->customer->name }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        {{ $booking->service->name }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        {{ number_format($booking->service->price, 2, ',', '.') }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">

                                            @if ($booking->status === BookingStatus::PENDING)
                                                <span
                                                    class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">
                                                    Pending
                                                </span>

                                                <a href="{{ route('bookings.edit', $booking->id) }}"
                                                    class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                                    Edit
                                                </a>

                                                <button type="button" wire:click="confirm({{ $booking->id }})"
                                                    class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-gray-800 data-loading:pointer-events-none data-loading:opacity-50">
                                                    <span class="in-data-loading:hidden">
                                                        Confirm
                                                    </span>
                                                    <span class="not-in-data-loading:hidden">
                                                        Confirming...
                                                    </span>
                                                </button>

                                                <button type="button" wire:click="cancel({{ $booking->id }})"
                                                    class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50 data-loading:pointer-events-none data-loading:opacity-50">
                                                    <span class="in-data-loading:hidden">
                                                        Cancel
                                                    </span>
                                                    <span class="not-in-data-loading:hidden">
                                                        Cancelling...
                                                    </span>
                                                </button>
                                            @elseif ($booking->status === BookingStatus::CONFIRMED)
                                                <span
                                                    class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                                                    Confirmed
                                                </span>

                                                <a href="{{ route('bookings.edit', $booking->id) }}"
                                                    class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">
                                                    Edit
                                                </a>

                                                <button type="button" wire:click="complete({{ $booking->id }})"
                                                    class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-gray-800 data-loading:pointer-events-none data-loading:opacity-50">
                                                    <span class="in-data-loading:hidden">
                                                        Complete
                                                    </span>
                                                    <span class="not-in-data-loading:hidden">
                                                        Completing...
                                                    </span>
                                                </button>

                                                <button type="button" wire:click="cancel({{ $booking->id }})"
                                                    class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50 data-loading:pointer-events-none data-loading:opacity-50">
                                                    <span class="in-data-loading:hidden">
                                                        Cancel
                                                    </span>
                                                    <span class="not-in-data-loading:hidden">
                                                        Cancelling...
                                                    </span>
                                                </button>
                                            @elseif ($booking->status === BookingStatus::CANCELLED)
                                                <span
                                                    class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800">
                                                    Cancelled
                                                </span>
                                            @elseif ($booking->status === BookingStatus::COMPLETED)
                                                <span
                                                    class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                                                    Completed
                                                </span>
                                            @endif

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>
            </div>
        @endif

    </div>
</div>
