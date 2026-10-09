<?php

use App\Enums\BookingDateFilter;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Queries\Bookings\BookingsQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns bookings for the given user with customer and service relations', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
        'status' => BookingStatus::PENDING,
    ]);

    $otherUser = User::factory()->create();

    $otherCustomer = Customer::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $otherService = Service::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    Booking::factory()->create([
        'user_id' => $otherUser->id,
        'customer_id' => $otherCustomer->id,
        'service_id' => $otherService->id,
    ]);

    $bookings = app(BookingsQuery::class)->get($user);

    expect($bookings)
        ->toHaveCount(1)
        ->and($bookings->first()->id)
        ->toBe($booking->id)
        ->and($bookings->first()->relationLoaded('customer'))
        ->toBeTrue()
        ->and($bookings->first()->relationLoaded('service'))
        ->toBeTrue();
});

it('returns bookings ordered by start time', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
    ]);

    $laterBooking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(2)->addHour(),
    ]);

    $earlierBooking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
    ]);

    $bookings = app(BookingsQuery::class)->get($user);

    expect($bookings->pluck('id')->all())
        ->toBe([
            $earlierBooking->id,
            $laterBooking->id,
        ]);
});

it('filters bookings by status', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
    ]);

    $pendingBooking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $bookings = app(BookingsQuery::class)->get(
        user: $user,
        status: BookingStatus::PENDING,
    );

    expect($bookings)
        ->toHaveCount(1)
        ->and($bookings->first()->id)
        ->toBe($pendingBooking->id);
});

it('filters bookings by date', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-30 12:00'));

    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
    ]);

    $past = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-09-29 14:00',
        'ends_at' => '2026-09-29 15:00',
    ]);

    $today = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-09-30 14:00',
        'ends_at' => '2026-09-30 15:00',
    ]);

    $upcoming = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 14:00',
        'ends_at' => '2026-10-01 15:00',
    ]);

    $query = app(BookingsQuery::class);

    expect($query->get($user, dateFilter: BookingDateFilter::TODAY)->pluck('id'))
        ->toEqual(collect([$today->id]));

    expect($query->get($user, dateFilter: BookingDateFilter::UPCOMING)->pluck('id'))
        ->toEqual(collect([$upcoming->id]));

    expect($query->get($user, dateFilter: BookingDateFilter::PAST)->pluck('id'))
        ->toEqual(collect([$past->id]));
});

it('filters bookings by status and date', function () {
    Carbon::setTestNow('2026-09-30 12:00');

    $user = User::factory()->create();

    $todayPending = Booking::factory()->for($user)->create([
        'status' => BookingStatus::PENDING,
        'starts_at' => '2026-09-30 14:00',
        'ends_at' => '2026-09-30 15:00',
    ]);

    Booking::factory()->for($user)->create([
        'status' => BookingStatus::CONFIRMED,
        'starts_at' => '2026-09-30 16:00',
        'ends_at' => '2026-09-30 17:00',
    ]);

    Booking::factory()->for($user)->create([
        'status' => BookingStatus::PENDING,
        'starts_at' => '2026-10-01 14:00',
        'ends_at' => '2026-10-01 15:00',
    ]);

    $query = app(BookingsQuery::class);

    expect($query->get(
        user: $user,
        status: BookingStatus::PENDING,
        dateFilter: BookingDateFilter::TODAY,
    )->pluck('id')->all())
        ->toBe([$todayPending->id]);

    Carbon::setTestNow();
});

it('includes bookings starting at the beginning of tomorrow in upcoming bookings', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-30 12:00'));

    $user = User::factory()->create();

    $booking = Booking::factory()->for($user)->create([
        'starts_at' => '2026-10-01 00:00',
        'ends_at' => '2026-10-01 01:00',
    ]);

    $bookings = app(BookingsQuery::class)->get(
        user: $user,
        dateFilter: BookingDateFilter::UPCOMING,
    );

    expect($bookings->pluck('id')->all())
        ->toBe([$booking->id]);

    Carbon::setTestNow();
});
