<?php

use App\Actions\Bookings\CreateBooking;
use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Exceptions\Bookings\BookingSlotUnavailableException;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a booking', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $booking = app(CreateBooking::class)->create(
        user: $user,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 14:00:00',
            'ends_at' => '2026-10-01 15:00:00',
        ],
    );

    expect($booking)
        ->toBeInstanceOf(Booking::class)
        ->and($booking->status)->toBe(BookingStatus::PENDING);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING->value,
    ]);
});

it('rejects a customer belonging to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    app(CreateBooking::class)->create(
        user: $user,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 14:00:00',
            'ends_at' => '2026-10-01 15:00:00',
        ],
    );
})->throws(ModelNotFoundException::class);

it('rejects a service belonging to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $otherUser->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    app(CreateBooking::class)->create(
        user: $user,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 14:00:00',
            'ends_at' => '2026-10-01 15:00:00',
        ],
    );
})->throws(ModelNotFoundException::class);

it('rejects an inactive service', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => false,
    ]);

    app(CreateBooking::class)->create(
        user: $user,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 14:00:00',
            'ends_at' => '2026-10-01 15:00:00',
        ],
    );
})->throws(
    BookingException::class,
    'The selected service is currently inactive.'
);

it('rejects an invalid time range', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    app(CreateBooking::class)->create(
        user: $user,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 15:00:00',
            'ends_at' => '2026-10-01 14:00:00',
        ],
    );
})->throws(
    BookingException::class,
    'The booking end time must be after the start time.'
);

it('rejects an overlapping booking when the existing booking occupies the slot', function (BookingStatus $status) {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 14:00:00',
        'ends_at' => '2026-10-01 15:00:00',
        'status' => $status,
    ]);

    app(CreateBooking::class)->create(
        user: $user,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 14:30:00',
            'ends_at' => '2026-10-01 15:30:00',
        ],
    );
})->throws(
    BookingSlotUnavailableException::class,
)->with([
    BookingStatus::PENDING,
    BookingStatus::CONFIRMED,
]);

it('allows an overlapping booking when the existing booking does not occupy the slot', function (BookingStatus $status) {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 14:00:00',
        'ends_at' => '2026-10-01 15:00:00',
        'status' => $status,
    ]);

    $booking = app(CreateBooking::class)->create(
        user: $user,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 14:30:00',
            'ends_at' => '2026-10-01 15:30:00',
        ],
    );

    expect($booking->status)->toBe(BookingStatus::PENDING);
})->with([
    BookingStatus::CANCELLED,
    BookingStatus::COMPLETED,
]);

it('allows adjacent bookings', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 14:00:00',
        'ends_at' => '2026-10-01 15:00:00',
        'status' => BookingStatus::CONFIRMED,
    ]);

    $booking = app(CreateBooking::class)->create(
        user: $user,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 15:00:00',
            'ends_at' => '2026-10-01 16:00:00',
        ],
    );

    expect($booking->status)->toBe(BookingStatus::PENDING);
});

it('rejects a booking whose duration does not match the service', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 4,
        'opens_at' => '09:00:00',
        'closes_at' => '17:00:00',
        'is_closed' => false,
    ]);

    app(CreateBooking::class)->create(
        user: $user,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 14:00:00',
            'ends_at' => '2026-10-01 15:30:00',
        ],
    );
})->throws(BookingException::class);

it('rejects a booking with identical start and end times', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    app(CreateBooking::class)->create(
        user: $user,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 14:00:00',
            'ends_at' => '2026-10-01 14:00:00',
        ],
    );
})->throws(
    BookingException::class,
    'The booking end time must be after the start time.'
);
