<?php

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Queries\Bookings\BookingsCalendarQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns bookings belonging to the user within the requested period', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create();

    $service = Service::factory()
        ->for($user)
        ->create();

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-05 10:00:00',
            'ends_at' => '2026-10-05 11:00:00',
        ]);

    $result = app(BookingsCalendarQuery::class)->get(
        user: $user,
        from: now()->parse('2026-10-05 00:00:00'),
        to: now()->parse('2026-10-06 00:00:00'),
    );

    expect($result)
        ->toHaveCount(1)
        ->first()->id->toBe($booking->id);
});

it('returns bookings that overlap the requested period', function (
    string $startsAt,
    string $endsAt,
) {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create();

    $service = Service::factory()
        ->for($user)
        ->create();

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

    $result = app(BookingsCalendarQuery::class)->get(
        user: $user,
        from: Carbon::parse('2026-10-05 00:00:00'),
        to: Carbon::parse('2026-10-06 00:00:00'),
    );

    expect($result)
        ->toHaveCount(1)
        ->first()->id->toBe($booking->id);
})->with([
    'starts before and ends inside' => [
        '2026-10-04 23:30:00',
        '2026-10-05 00:30:00',
    ],
    'starts inside and ends after' => [
        '2026-10-05 23:30:00',
        '2026-10-06 00:30:00',
    ],
    'contains entire period' => [
        '2026-10-04 00:00:00',
        '2026-10-07 00:00:00',
    ],
]);

it('does not return bookings that only touch the period boundary', function (
    string $startsAt,
    string $endsAt,
) {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create();

    $service = Service::factory()
        ->for($user)
        ->create();

    Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

    $result = app(BookingsCalendarQuery::class)->get(
        user: $user,
        from: Carbon::parse('2026-10-05 00:00:00'),
        to: Carbon::parse('2026-10-06 00:00:00'),
    );

    expect($result)->toBeEmpty();
})->with([
    'ends exactly at period start' => [
        '2026-10-04 23:00:00',
        '2026-10-05 00:00:00',
    ],
    'starts exactly at period end' => [
        '2026-10-06 00:00:00',
        '2026-10-06 01:00:00',
    ],
]);

it('does not return bookings belonging to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $customer = Customer::factory()
        ->for($otherUser)
        ->create();

    $service = Service::factory()
        ->for($otherUser)
        ->create();

    $otherBooking = Booking::factory()
        ->for($otherUser)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-05 10:00:00',
            'ends_at' => '2026-10-05 11:00:00',
        ]);

    $result = app(BookingsCalendarQuery::class)->get(
        user: $user,
        from: Carbon::parse('2026-10-05 00:00:00'),
        to: Carbon::parse('2026-10-06 00:00:00'),
    );

    expect($result)->toBeEmpty();
});

it('returns bookings ordered by start time', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create();

    $service = Service::factory()
        ->for($user)
        ->create();

    $laterBooking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-05 14:00:00',
            'ends_at' => '2026-10-05 15:00:00',
        ]);

    $earlierBooking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-05 10:00:00',
            'ends_at' => '2026-10-05 11:00:00',
        ]);

    $result = app(BookingsCalendarQuery::class)->get(
        user: $user,
        from: Carbon::parse('2026-10-05 00:00:00'),
        to: Carbon::parse('2026-10-06 00:00:00'),
    );

    expect($result)
        ->toHaveCount(2)
        ->sequence(
            fn($booking) => $booking->id->toBe($earlierBooking->id),
            fn($booking) => $booking->id->toBe($laterBooking->id),
        );
});

it('eager loads customer and service relationships', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create();

    $service = Service::factory()
        ->for($user)
        ->create();

    Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-05 10:00:00',
            'ends_at' => '2026-10-05 11:00:00',
        ]);

    $result = app(BookingsCalendarQuery::class)->get(
        user: $user,
        from: Carbon::parse('2026-10-05 00:00:00'),
        to: Carbon::parse('2026-10-06 00:00:00'),
    );

    $booking = $result->first();

    expect($booking->relationLoaded('customer'))->toBeTrue()
        ->and($booking->relationLoaded('service'))->toBeTrue();
});
