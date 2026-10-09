<?php

use App\Actions\Bookings\UpdateBooking;
use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Exceptions\Bookings\BookingSlotUnavailableException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates a pending or confirmed booking', function (BookingStatus $status) {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Doe',
    ]);

    $newCustomer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'Jane Doe',
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $newService = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 90,
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 14:00:00',
        'ends_at' => '2026-10-01 15:00:00',
        'status' => $status,
        'notes' => 'Old notes.',
    ]);

    $updatedBooking = app(UpdateBooking::class)->update(
        user: $user,
        bookingId: $booking->id,
        customerId: $newCustomer->id,
        serviceId: $newService->id,
        data: [
            'starts_at' => '2026-10-01 16:00:00',
            'ends_at' => '2026-10-01 17:30:00',
            'notes' => 'Updated notes.',
        ],
    );

    expect($updatedBooking->fresh())
        ->customer_id->toBe($newCustomer->id)
        ->service_id->toBe($newService->id)
        ->starts_at->format('Y-m-d H:i:s')->toBe('2026-10-01 16:00:00')
        ->ends_at->format('Y-m-d H:i:s')->toBe('2026-10-01 17:30:00')
        ->status->toBe($status)
        ->notes->toBe('Updated notes.');
})->with([
    BookingStatus::PENDING,
    BookingStatus::CONFIRMED,
]);

it('rejects editing a cancelled or completed booking', function (BookingStatus $status) {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => $status,
    ]);

    app(UpdateBooking::class)->update(
        user: $user,
        bookingId: $booking->id,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 16:00:00',
            'ends_at' => '2026-10-01 17:00:00',
        ],
    );
})->throws(
    BookingException::class,
    'Only pending or confirmed bookings can be edited.'
)->with([
    BookingStatus::CANCELLED,
    BookingStatus::COMPLETED,
]);

it('rejects a customer belonging to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $otherCustomer = Customer::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    app(UpdateBooking::class)->update(
        user: $user,
        bookingId: $booking->id,
        customerId: $otherCustomer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 16:00:00',
            'ends_at' => '2026-10-01 17:00:00',
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
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $otherService = Service::factory()->create([
        'user_id' => $otherUser->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    app(UpdateBooking::class)->update(
        user: $user,
        bookingId: $booking->id,
        customerId: $customer->id,
        serviceId: $otherService->id,
        data: [
            'starts_at' => '2026-10-01 16:00:00',
            'ends_at' => '2026-10-01 17:00:00',
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

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    app(UpdateBooking::class)->update(
        user: $user,
        bookingId: $booking->id,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 16:00:00',
            'ends_at' => '2026-10-01 17:00:00',
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

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    app(UpdateBooking::class)->update(
        user: $user,
        bookingId: $booking->id,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 17:00:00',
            'ends_at' => '2026-10-01 16:00:00',
        ],
    );
})->throws(
    BookingException::class,
    'The booking end time must be after the start time.'
);

it('rejects an updated booking whose duration does not match the service', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 14:00:00',
        'ends_at' => '2026-10-01 15:00:00',
        'status' => BookingStatus::PENDING,
    ]);

    app(UpdateBooking::class)->update(
        user: $user,
        bookingId: $booking->id,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 16:00:00',
            'ends_at' => '2026-10-01 17:30:00',
        ],
    );
})->throws(
    BookingException::class,
    'The booking duration must match the selected service.'
);

it('rejects a booking with identical start and end times', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    app(UpdateBooking::class)->update(
        user: $user,
        bookingId: $booking->id,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 16:00:00',
            'ends_at' => '2026-10-01 16:00:00',
        ],
    );
})->throws(
    BookingException::class,
    'The booking end time must be after the start time.'
);

it('rejects an overlapping booking', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 14:00:00',
        'ends_at' => '2026-10-01 15:00:00',
        'status' => BookingStatus::PENDING,
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 16:00:00',
        'ends_at' => '2026-10-01 17:00:00',
        'status' => BookingStatus::CONFIRMED,
    ]);

    app(UpdateBooking::class)->update(
        user: $user,
        bookingId: $booking->id,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 16:30:00',
            'ends_at' => '2026-10-01 17:30:00',
        ],
    );
})->throws(
    BookingSlotUnavailableException::class,
);

it('allows the booking to keep its current time without self-overlap', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 14:00:00',
        'ends_at' => '2026-10-01 15:00:00',
        'status' => BookingStatus::CONFIRMED,
    ]);

    $updatedBooking = app(UpdateBooking::class)->update(
        user: $user,
        bookingId: $booking->id,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 14:00:00',
            'ends_at' => '2026-10-01 15:00:00',
        ],
    );

    expect($updatedBooking->fresh()->id)
        ->toBe($booking->id);
});

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

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 14:00:00',
        'ends_at' => '2026-10-01 15:00:00',
        'status' => BookingStatus::CONFIRMED,
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 16:00:00',
        'ends_at' => '2026-10-01 17:00:00',
        'status' => BookingStatus::CONFIRMED,
    ]);

    $updatedBooking = app(UpdateBooking::class)->update(
        user: $user,
        bookingId: $booking->id,
        customerId: $customer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => '2026-10-01 15:00:00',
            'ends_at' => '2026-10-01 16:00:00',
        ],
    );

    expect($updatedBooking->fresh()->starts_at->format('Y-m-d H:i:s'))
        ->toBe('2026-10-01 15:00:00');
});
