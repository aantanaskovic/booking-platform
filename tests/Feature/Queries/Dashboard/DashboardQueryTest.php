<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Queries\Dashboard\DashboardQuery;
use Carbon\Carbon;

it('counts todays bookings for the user', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');

    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create();
    $service = Service::factory()->for($user)->create();

    Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-20 10:00:00',
        'ends_at' => '2026-10-20 11:00:00',
    ]);

    Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-20 14:00:00',
        'ends_at' => '2026-10-20 15:00:00',
    ]);

    Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-21 10:00:00',
        'ends_at' => '2026-10-21 11:00:00',
    ]);

    $otherUser = User::factory()->create();

    $otherCustomer = Customer::factory()->for($otherUser)->create();
    $otherService = Service::factory()->for($otherUser)->create();

    Booking::factory()->for($otherUser)->create([
        'customer_id' => $otherCustomer->id,
        'service_id' => $otherService->id,
        'starts_at' => '2026-10-20 16:00:00',
        'ends_at' => '2026-10-20 17:00:00',
    ]);

    $count = app(DashboardQuery::class)->todayBookingsCount($user);

    expect($count)->toBe(2);
});

it('counts pending bookings for the user', function (
    BookingStatus $status,
    bool $shouldBeCounted,
) {
    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create();
    $service = Service::factory()->for($user)->create();

    Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-20 10:00:00',
        'ends_at' => '2026-10-20 11:00:00',
        'status' => $status,
    ]);

    $count = app(DashboardQuery::class)->pendingBookingsCount($user);

    expect($count)->toBe($shouldBeCounted ? 1 : 0);
})->with([
    'pending' => [
        BookingStatus::PENDING,
        true,
    ],
    'confirmed' => [
        BookingStatus::CONFIRMED,
        false,
    ],
    'cancelled' => [
        BookingStatus::CANCELLED,
        false,
    ],
    'completed' => [
        BookingStatus::COMPLETED,
        false,
    ],
]);

it('counts upcoming bookings for the user', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');

    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create();
    $service = Service::factory()->for($user)->create();

    Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-20 14:00:00',
        'ends_at' => '2026-10-20 15:00:00',
    ]);

    Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-21 10:00:00',
        'ends_at' => '2026-10-21 11:00:00',
    ]);

    Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-22 10:00:00',
        'ends_at' => '2026-10-22 11:00:00',
    ]);

    $count = app(DashboardQuery::class)->upcomingBookingsCount($user);

    expect($count)->toBe(2);
});

it('counts customers for the user', function () {
    $user = User::factory()->create();

    Customer::factory()
        ->for($user)
        ->count(3)
        ->create();

    $otherUser = User::factory()->create();

    Customer::factory()
        ->for($otherUser)
        ->count(2)
        ->create();

    $count = app(DashboardQuery::class)->customersCount($user);

    expect($count)->toBe(3);
});

it('returns todays bookings for the user ordered by start time', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');

    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create();
    $service = Service::factory()->for($user)->create();

    $laterBooking = Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-20 14:00:00',
        'ends_at' => '2026-10-20 15:00:00',
    ]);

    $earlierBooking = Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-20 10:00:00',
        'ends_at' => '2026-10-20 11:00:00',
    ]);

    Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-21 10:00:00',
        'ends_at' => '2026-10-21 11:00:00',
    ]);

    $bookings = app(DashboardQuery::class)->todayBookings($user);

    expect($bookings)
        ->toHaveCount(2)
        ->sequence(
            fn($booking) => $booking->id->toBe($earlierBooking->id),
            fn($booking) => $booking->id->toBe($laterBooking->id),
        );
});

it('eager loads customer and service for todays bookings', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');

    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create();
    $service = Service::factory()->for($user)->create();

    Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-20 10:00:00',
        'ends_at' => '2026-10-20 11:00:00',
    ]);

    $bookings = app(DashboardQuery::class)->todayBookings($user);

    expect($bookings->first()->relationLoaded('customer'))->toBeTrue()
        ->and($bookings->first()->relationLoaded('service'))->toBeTrue();
});

it('returns the five nearest upcoming bookings for the user', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');

    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create();
    $service = Service::factory()->for($user)->create();

    Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-20 14:00:00',
        'ends_at' => '2026-10-20 15:00:00',
    ]);

    $bookings = collect();

    foreach (range(21, 26) as $day) {
        $bookings->push(
            Booking::factory()->for($user)->create([
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'starts_at' => "2026-10-{$day} 10:00:00",
                'ends_at' => "2026-10-{$day} 11:00:00",
            ])
        );
    }

    $result = app(DashboardQuery::class)->upcomingBookings($user);

    expect($result)
        ->toHaveCount(5)
        ->sequence(
            fn($booking) => $booking->id->toBe($bookings[0]->id),
            fn($booking) => $booking->id->toBe($bookings[1]->id),
            fn($booking) => $booking->id->toBe($bookings[2]->id),
            fn($booking) => $booking->id->toBe($bookings[3]->id),
            fn($booking) => $booking->id->toBe($bookings[4]->id),
        );
});
