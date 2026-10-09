<?php

use App\Actions\Bookings\CompleteBooking;
use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('completes a confirmed booking', function () {
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
        'status' => BookingStatus::CONFIRMED,
    ]);

    $completedBooking = app(CompleteBooking::class)->complete(
        user: $user,
        bookingId: $booking->id,
    );

    expect($completedBooking->status)
        ->toBe(BookingStatus::COMPLETED);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => BookingStatus::COMPLETED->value,
    ]);
});

it('cannot complete a non-confirmed booking', function (BookingStatus $status) {
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

    expect(fn() => app(CompleteBooking::class)->complete(
        user: $user,
        bookingId: $booking->id,
    ))->toThrow(
        BookingException::class,
        'Only confirmed bookings can be completed.'
    );
})->with([
    'pending' => BookingStatus::PENDING,
    'cancelled' => BookingStatus::CANCELLED,
    'completed' => BookingStatus::COMPLETED,
]);

it('cannot complete a booking belonging to another user', function () {
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
        'status' => BookingStatus::CONFIRMED,
    ]);

    expect(fn() => app(CompleteBooking::class)->complete(
        user: $user,
        bookingId: $booking->id,
    ))->toThrow(ModelNotFoundException::class);
});
