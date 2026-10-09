<?php

use App\Queries\Dashboard\DashboardQuery;
use Livewire\Component;

new class extends Component {
    public int $todayBookingsCount = 0;
    public int $upcomingBookingsCount = 0;
    public int $pendingBookingsCount = 0;
    public int $customersCount = 0;

    public $todayBookings;
    public $upcomingBookings;

    public function mount(DashboardQuery $dashboardQuery): void
    {
        $user = auth()->user();

        $this->todayBookingsCount = $dashboardQuery->todayBookingsCount($user);
        $this->upcomingBookingsCount = $dashboardQuery->upcomingBookingsCount($user);
        $this->pendingBookingsCount = $dashboardQuery->pendingBookingsCount($user);
        $this->customersCount = $dashboardQuery->customersCount($user);

        $this->todayBookings = $dashboardQuery->todayBookings($user);
        $this->upcomingBookings = $dashboardQuery->upcomingBookings($user);
    }
};
?>

<div class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                Dashboard
            </h1>

            <p class="mt-2 text-sm text-gray-600">
                Overview of your business activity.
            </p>
        </div>

        <section class="mb-8">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Quick actions
                </h2>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('bookings.create') }}"
                    class="flex items-center gap-3 rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 transition hover:border-indigo-200 hover:bg-indigo-100">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-indigo-100 text-lg font-semibold text-indigo-700">
                        +
                    </span>

                    <div>
                        <h3 class="text-sm font-semibold text-indigo-900">
                            New booking
                        </h3>

                        <p class="text-xs text-indigo-700">
                            Schedule appointment
                        </p>
                    </div>
                </a>

                <a href="{{ route('customers.create') }}"
                    class="flex items-center gap-3 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 transition hover:border-emerald-200 hover:bg-emerald-100">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-lg font-semibold text-emerald-700">
                        +
                    </span>

                    <div>
                        <h3 class="text-sm font-semibold text-emerald-900">
                            New customer
                        </h3>

                        <p class="text-xs text-emerald-700">
                            Add customer
                        </p>
                    </div>
                </a>

                <a href="{{ route('services.create') }}"
                    class="flex items-center gap-3 rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 transition hover:border-amber-200 hover:bg-amber-100">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-amber-100 text-lg font-semibold text-amber-700">
                        +
                    </span>

                    <div>
                        <h3 class="text-sm font-semibold text-amber-900">
                            New service
                        </h3>

                        <p class="text-xs text-amber-700">
                            Create a service
                        </p>
                    </div>
                </a>

                <a href="{{ route('bookings.calendar') }}"
                    class="flex items-center gap-3 rounded-lg border border-violet-100 bg-violet-50 px-4 py-3 transition hover:border-violet-200 hover:bg-violet-100">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-violet-100 text-lg font-semibold text-violet-700">
                        ↗
                    </span>

                    <div>
                        <h3 class="text-sm font-semibold text-violet-900">
                            View calendar
                        </h3>

                        <p class="text-xs text-violet-700">
                            View appointments
                        </p>
                    </div>
                </a>
            </div>
        </section>

        <section class="mb-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Today
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $todayBookingsCount }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Upcoming
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $upcomingBookingsCount }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Pending
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $pendingBookingsCount }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Customers
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $customersCount }}
                    </p>
                </div>

            </div>
        </section>

        <section class="mb-8">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Today's bookings
                </h2>
            </div>

            @forelse ($todayBookings as $booking)
                <div class="mb-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ $booking->starts_at->format('d.m.Y. H:i') }}
                            </p>

                            <h3 class="mt-1 text-lg font-semibold text-gray-900">
                                {{ $booking->customer->name }}
                            </h3>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ $booking->service->name }}
                            </p>
                        </div>

                        <div>
                            <span
                                class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
                                {{ $booking->status->value }}
                            </span>
                        </div>

                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        No bookings today.
                    </h3>

                    <p class="mt-2 text-sm text-gray-500">
                        You don't have any bookings scheduled for today.
                    </p>
                </div>
            @endforelse
        </section>

        <section>
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900">
                    Upcoming bookings
                </h2>
            </div>

            @forelse ($upcomingBookings as $booking)
                <div class="mb-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                {{ $booking->starts_at->format('d.m.Y. H:i') }}
                            </p>

                            <h3 class="mt-1 text-lg font-semibold text-gray-900">
                                {{ $booking->customer->name }}
                            </h3>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ $booking->service->name }}
                            </p>
                        </div>

                        <div>
                            <span
                                class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
                                {{ $booking->status->value }}
                            </span>
                        </div>

                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        No upcoming bookings.
                    </h3>

                    <p class="mt-2 text-sm text-gray-500">
                        You don't have any upcoming bookings yet.
                    </p>
                </div>
            @endforelse
        </section>

    </div>
</div>
