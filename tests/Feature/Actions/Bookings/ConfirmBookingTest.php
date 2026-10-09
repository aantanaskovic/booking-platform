<?php

use App\Actions\Bookings\ConfirmBooking;
use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingCancellationRequestedNotification;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('confirms a pending booking', function () {
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
        'status' => BookingStatus::PENDING,
    ]);

    $confirmedBooking = app(ConfirmBooking::class)->confirm(
        user: $user,
        bookingId: $booking->id,
    );

    expect($confirmedBooking->status)
        ->toBe(BookingStatus::CONFIRMED);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => BookingStatus::CONFIRMED->value,
    ]);
});

it('cannot confirm a non-pending booking', function (BookingStatus $status) {
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
        'status' => $status,
    ]);

    expect(fn() => app(ConfirmBooking::class)->confirm(
        user: $user,
        bookingId: $booking->id,
    ))->toThrow(
        BookingException::class,
        'Only pending bookings can be confirmed.'
    );
})->with([
    'confirmed' => BookingStatus::CONFIRMED,
    'cancelled' => BookingStatus::CANCELLED,
    'completed' => BookingStatus::COMPLETED,
]);

it('cannot confirm a booking belonging to another user', function () {
    $user = User::factory()->create();

    $otherUser = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $otherUser->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    expect(fn() => app(ConfirmBooking::class)->confirm(
        user: $user,
        bookingId: $booking->id,
    ))->toThrow(ModelNotFoundException::class);
});

it('sends a confirmation notification to the customer', function () {
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

    $action = app(ConfirmBooking::class);

    $action->confirm($user, $booking->id);

    Notification::assertSentTo(
        $customer,
        BookingConfirmedNotification::class,
    );
});

it('sends a cancellation management notification when a booking is confirmed', function () {
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
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
        ]);

    app(ConfirmBooking::class)->confirm(
        user: $user,
        bookingId: $booking->id,
    );

    Notification::assertSentTo(
        $customer,
        BookingCancellationRequestedNotification::class,
    );
});
