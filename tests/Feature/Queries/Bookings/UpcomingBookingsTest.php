<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Queries\Bookings\UpcomingBookings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns upcoming bookings for the given user', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    $now = now();

    $bookingStartsAt = $now->copy()->addDay()->setTime(14, 0);
    $bookingEndsAt = $bookingStartsAt->copy()->addHour();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => $bookingStartsAt,
        'ends_at' => $bookingEndsAt,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $otherUser = User::factory()->create();

    $otherCustomer = Customer::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $otherService = Service::factory()->create([
        'user_id' => $otherUser->id,
        'is_active' => true,
    ]);

    $otherBookingStartsAt = $now->copy()->addDays(2)->setTime(10, 0);
    $otherBookingEndsAt = $otherBookingStartsAt->copy()->addHour();

    Booking::factory()->create([
        'user_id' => $otherUser->id,
        'customer_id' => $otherCustomer->id,
        'service_id' => $otherService->id,
        'starts_at' => $otherBookingStartsAt,
        'ends_at' => $otherBookingEndsAt,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $cancelledBookingStartsAt = $now->copy()->addDays(3)->setTime(12, 0);
    $cancelledBookingEndsAt = $cancelledBookingStartsAt->copy()->addHour();

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => $cancelledBookingStartsAt,
        'ends_at' => $cancelledBookingEndsAt,
        'status' => BookingStatus::CANCELLED,
    ]);

    $bookings = app(UpcomingBookings::class)->get($user);

    expect($bookings)
        ->toHaveCount(1)
        ->and($bookings->first()->is($booking))->toBeTrue();

    expect($bookings->first()->relationLoaded('customer'))->toBeTrue()
        ->and($bookings->first()->relationLoaded('service'))->toBeTrue();
});

it('returns upcoming bookings ordered by start time', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    $now = now();

    $laterBookingStartsAt = $now->copy()->addDays(3)->setTime(14, 0);
    $laterBookingEndsAt = $laterBookingStartsAt->copy()->addHour();

    $laterBooking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => $laterBookingStartsAt,
        'ends_at' => $laterBookingEndsAt,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $earlierBookingStartsAt = $now->copy()->addDay()->setTime(14, 0);
    $earlierBookingEndsAt = $earlierBookingStartsAt->copy()->addHour();

    $earlierBooking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => $earlierBookingStartsAt,
        'ends_at' => $earlierBookingEndsAt,
        'status' => BookingStatus::CONFIRMED,
    ]);

    $bookings = app(UpcomingBookings::class)->get($user);

    expect($bookings)
        ->toHaveCount(2)
        ->and($bookings->first()->is($earlierBooking))->toBeTrue()
        ->and($bookings->last()->is($laterBooking))->toBeTrue();
});
