<?php

use App\Enums\BookingStatus;
use App\Queries\Bookings\BookingsCalendarQuery;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    #[Url]
    public ?int $year = null;

    #[Url]
    public ?int $month = null;

    public $bookings;

    public array $calendarDays = [];

    public function mount(): void
    {
        $date = now();

        $this->year ??= $date->year;
        $this->month ??= $date->month;

        $this->refreshCalendar();
    }

    public function nextMonth(): void
    {
        $date = Carbon::create(year: $this->year, month: $this->month, day: 1)->addMonth();

        $this->year = $date->year;
        $this->month = $date->month;

        $this->refreshCalendar();
    }

    public function previousMonth(): void
    {
        $date = Carbon::create(year: $this->year, month: $this->month, day: 1)->subMonth();

        $this->year = $date->year;
        $this->month = $date->month;

        $this->refreshCalendar();
    }

    public function goToCurrentMonth(): void
    {
        $date = now();

        $this->year = $date->year;
        $this->month = $date->month;

        $this->refreshCalendar();
    }

    private function buildCalendarDays(): void
    {
        $firstDay = Carbon::create(year: $this->year, month: $this->month, day: 1)->startOfMonth();

        $lastDay = $firstDay->copy()->endOfMonth();

        $start = $firstDay->copy()->subDays($firstDay->dayOfWeekIso - 1);

        $end = $lastDay->copy()->addDays(7 - $lastDay->dayOfWeekIso);

        $this->calendarDays = collect()
            ->times(
                $start->diffInDays($end) + 1,
                fn($index) => [
                    'date' => $start->copy()->addDays($index - 1),
                ],
            )
            ->all();
    }

    private function loadBookings(): void
    {
        $from = Carbon::create(year: $this->year, month: $this->month, day: 1)->startOfMonth();

        $to = $from->copy()->addMonth();

        $this->bookings = app(BookingsCalendarQuery::class)->get(user: auth()->user(), from: $from, to: $to);
    }

    private function refreshCalendar(): void
    {
        $this->buildCalendarDays();
        $this->loadBookings();
    }
};
?>

<div class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <a href="{{ route('bookings.index') }}"
                    class="mb-4 inline-flex items-center gap-2 text-sm font-medium text-gray-600 transition hover:text-gray-900">
                    <span aria-hidden="true">←</span>
                    Back to bookings
                </a>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900"> Bookings Calendar </h1>
                <p class="mt-2 text-sm text-gray-600"> View your bookings by month. </p>
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white shadow-md">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4"> <button type="button"
                    wire:click="previousMonth"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Previous </button>
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        {{ Carbon::create($year, $month, 1)->format('F Y') }} </h2> <button type="button"
                        wire:click="goToCurrentMonth"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Today </button>
                </div> <button type="button" wire:click="nextMonth"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Next </button>
            </div>
            <div class="grid grid-cols-7 border-b border-gray-200">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                    <div class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                        {{ $day }} </div>
                @endforeach
            </div>
            <div class="grid grid-cols-7">
                @foreach ($calendarDays as $calendarDay)
                    @php
                        $date = $calendarDay['date'];
                        $isCurrentMonth = $date->month === $month;
                        $isToday = $date->isToday();

                        $dayBookings = $bookings->filter(fn($booking) => $booking->starts_at->isSameDay($date));
                    @endphp

                    <div data-date="{{ $date->toDateString() }}"
                        class="relative min-h-32 border-b border-r border-gray-200 p-3
                {{ $isCurrentMonth ? 'bg-white' : 'bg-gray-50' }}
                {{ $isToday ? 'ring-2 ring-inset ring-indigo-500' : '' }}">
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-medium
                    {{ $isCurrentMonth ? 'text-gray-900' : 'text-gray-400' }}
                    {{ $isToday ? 'bg-indigo-600 text-white' : '' }}">
                            {{ $date->day }}
                        </div>

                        @if ($date->isToday() || $date->isFuture())
                            <a href="{{ route('bookings.create', [
                                'date' => $date->toDateString(),
                                'returnRoute' => 'bookings.calendar',
                            ]) }}"
                                aria-label="Add booking for {{ $date->toFormattedDateString() }}" title="Add booking"
                                class="absolute bottom-2 right-2 inline-flex h-7 w-7 items-center justify-center rounded-md bg-emerald-50 text-lg font-medium text-emerald-600 shadow-sm transition hover:bg-emerald-100 hover:text-emerald-800">
                                <span aria-hidden="true">+</span>
                            </a>
                        @endif

                        <div class="mt-2 space-y-1">
                            @foreach ($dayBookings as $booking)
                                @php
                                    $bookingClasses = match ($booking->status) {
                                        BookingStatus::PENDING => 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100',

                                        BookingStatus::CONFIRMED => 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100',

                                        BookingStatus::CANCELLED,
                                        BookingStatus::COMPLETED
                                            => 'bg-gray-100 text-gray-500 hover:bg-gray-200',
                                    };
                                @endphp

                                <a href="{{ route('bookings.show', ['bookingId' => $booking->id]) }}"
                                    class="block rounded-md px-2 py-1 text-xs transition {{ $bookingClasses }}">
                                    <div class="font-medium">
                                        {{ $booking->starts_at->format('H:i') }}
                                    </div>

                                    <div class="font-medium">
                                        {{ $booking->customer->name }}
                                    </div>

                                    <div>
                                        {{ $booking->service->name }}
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
