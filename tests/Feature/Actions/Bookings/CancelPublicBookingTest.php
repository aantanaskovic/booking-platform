<?php

use App\Actions\Bookings\CancelPublicBooking;
use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use Illuminate\Support\Facades\Notification;

it('cancels a booking belonging to the customer', function (BookingStatus $status) {
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
            'status' => $status,
        ]);

    app(CancelPublicBooking::class)->cancel(
        customer: $customer,
        bookingId: $booking->id,
    );

    expect($booking->fresh()->status)
        ->toBe(BookingStatus::CANCELLED);
})->with([
    'pending booking' => BookingStatus::PENDING,
    'confirmed booking' => BookingStatus::CONFIRMED,
]);

it('does not cancel a booking belonging to another customer', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create();

    $otherCustomer = Customer::factory()
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
            'status' => BookingStatus::PENDING,
        ]);

    expect(fn() => app(CancelPublicBooking::class)->cancel(
        customer: $otherCustomer,
        bookingId: $booking->id,
    ))->toThrow(BookingException::class);

    expect($booking->fresh()->status)
        ->toBe(BookingStatus::PENDING);
});

it('sends a cancellation notification when the customer cancels a booking', function () {
    Notification::fake();

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
            'status' => BookingStatus::PENDING,
        ]);

    app(CancelPublicBooking::class)->cancel(
        customer: $customer,
        bookingId: $booking->id,
    );

    Notification::assertSentTo(
        $customer,
        BookingCancelledNotification::class,
    );
});

it('does not cancel a booking that cannot be cancelled', function (BookingStatus $status) {
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
            'status' => $status,
        ]);

    expect(fn() => app(CancelPublicBooking::class)->cancel(
        customer: $customer,
        bookingId: $booking->id,
    ))->toThrow(BookingException::class);

    expect($booking->fresh()->status)->toBe($status);
})->with([
    'already cancelled' => BookingStatus::CANCELLED,
    'completed' => BookingStatus::COMPLETED,
]);
