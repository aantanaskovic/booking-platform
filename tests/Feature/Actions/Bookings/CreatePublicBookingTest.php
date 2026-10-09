<?php

use App\Actions\Bookings\CreatePublicBooking;
use App\Exceptions\Bookings\BookingException;
use App\Exceptions\Bookings\BookingSlotUnavailableException;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;

it('creates a customer and booking', function () {
    $business = User::factory()->create();

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $startsAt = Carbon::tomorrow()->setTime(9, 0);
    $endsAt = $startsAt->copy()->addHour();

    $business->businessHours()->create([
        'day_of_week' => $startsAt->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    $action = app(CreatePublicBooking::class);

    $booking = $action->create(
        user: $business,
        serviceId: $service->id,
        customerData: [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+381 60 123 456',
            'notes' => 'First appointment.',
        ],
        bookingData: [
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
            'notes' => 'First appointment.',
        ],
    );

    expect($booking->user_id)->toBe($business->id)
        ->and($booking->customer_id)->not->toBeNull()
        ->and($booking->service_id)->toBe($service->id);

    $this->assertDatabaseHas('customers', [
        'user_id' => $business->id,
        'email' => 'john@example.com',
    ]);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'user_id' => $business->id,
        'service_id' => $service->id,
        'starts_at' => $startsAt->toDateTimeString(),
        'ends_at' => $endsAt->toDateTimeString(),
    ]);
});

it('rejects a second booking for the same time slot', function () {
    $business = User::factory()->create();

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $startsAt = Carbon::tomorrow()->setTime(9, 0);
    $endsAt = $startsAt->copy()->addHour();

    $business->businessHours()->create([
        'day_of_week' => $startsAt->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    $action = app(CreatePublicBooking::class);

    $bookingData = [
        'starts_at' => $startsAt->toDateTimeString(),
        'ends_at' => $endsAt->toDateTimeString(),
    ];

    $customerData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+381 60 123 456',
    ];

    $action->create(
        user: $business,
        serviceId: $service->id,
        customerData: $customerData,
        bookingData: $bookingData,
    );

    expect(fn() => $action->create(
        user: $business,
        serviceId: $service->id,
        customerData: $customerData,
        bookingData: $bookingData,
    ))->toThrow(BookingSlotUnavailableException::class);

    $this->assertDatabaseCount('bookings', 1);
    $this->assertDatabaseCount('customers', 1);
});

it('rolls back the customer when the booking cannot be created', function () {
    $business = User::factory()->create();

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $startsAt = Carbon::tomorrow()->setTime(9, 0);
    $endsAt = $startsAt->copy()->addHour();

    $business->businessHours()->create([
        'day_of_week' => $startsAt->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    $existingCustomer = $business->customers()->create([
        'name' => 'Existing Customer',
        'email' => 'existing@example.com',
        'phone' => '+381 60 999 999',
    ]);

    $action = app(CreatePublicBooking::class);

    $action->create(
        user: $business,
        serviceId: $service->id,
        customerData: [
            'name' => 'Existing Customer',
            'email' => 'existing@example.com',
            'phone' => '+381 60 999 999',
        ],
        bookingData: [
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
        ],
    );

    expect($business->bookings()->count())->toBe(1);

    expect(fn() => $action->create(
        user: $business,
        serviceId: $service->id,
        customerData: [
            'name' => 'New Customer',
            'email' => 'new@example.com',
            'phone' => '+381 60 111 111',
        ],
        bookingData: [
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
        ],
    ))->toThrow(
        BookingSlotUnavailableException::class,
    );

    $this->assertDatabaseMissing('customers', [
        'user_id' => $business->id,
        'email' => 'new@example.com',
    ]);

    $this->assertDatabaseCount('bookings', 1);
});

it('reuses an existing customer when creating a public booking', function () {
    $business = User::factory()->create();

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $startsAt = Carbon::tomorrow()->setTime(9, 0);
    $endsAt = $startsAt->copy()->addHour();

    $business->businessHours()->create([
        'day_of_week' => $startsAt->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    $customer = $business->customers()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+381 60 123 456',
    ]);

    $action = app(CreatePublicBooking::class);

    $booking = $action->create(
        user: $business,
        serviceId: $service->id,
        customerData: [
            'name' => 'John Updated',
            'email' => 'john@example.com',
            'phone' => '+381 60 999 999',
        ],
        bookingData: [
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
        ],
    );

    expect($booking->customer_id)->toBe($customer->id)
        ->and($business->customers()->count())->toBe(1);
});

it('does not reuse a customer from another business', function () {
    $businessA = User::factory()->create();
    $businessB = User::factory()->create();

    $service = Service::factory()->create([
        'user_id' => $businessB->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $startsAt = Carbon::tomorrow()->setTime(9, 0);
    $endsAt = $startsAt->copy()->addHour();

    $businessB->businessHours()->create([
        'day_of_week' => $startsAt->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    $customerA = $businessA->customers()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+381 60 123 456',
    ]);

    $action = app(CreatePublicBooking::class);

    $booking = $action->create(
        user: $businessB,
        serviceId: $service->id,
        customerData: [
            'name' => 'John Business B',
            'email' => 'john@example.com',
            'phone' => '+381 60 999 999',
        ],
        bookingData: [
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
        ],
    );

    expect($booking->customer_id)->not->toBe($customerA->id)
        ->and($booking->customer->user_id)->toBe($businessB->id)
        ->and($businessA->customers()->count())->toBe(1)
        ->and($businessB->customers()->count())->toBe(1);
});
