<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Queries\Bookings\AvailableBookingSlots;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns available slots based on business hours and service duration', function () {
    $this->travelTo(Carbon::parse('2026-10-05 08:00:00'));

    $user = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 1,
        'opens_at' => '09:00:00',
        'closes_at' => '12:00:00',
        'is_closed' => false,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $slots = app(AvailableBookingSlots::class)->get(
        user: $user,
        serviceId: $service->id,
        date: Carbon::parse('2026-10-05'),
    );

    expect($slots)
        ->toHaveCount(3)
        ->and($slots[0]['starts_at']->format('H:i'))->toBe('09:00')
        ->and($slots[0]['ends_at']->format('H:i'))->toBe('10:00')
        ->and($slots[1]['starts_at']->format('H:i'))->toBe('10:00')
        ->and($slots[1]['ends_at']->format('H:i'))->toBe('11:00')
        ->and($slots[2]['starts_at']->format('H:i'))->toBe('11:00')
        ->and($slots[2]['ends_at']->format('H:i'))->toBe('12:00');
});

it('does not return slots for a closed day', function () {
    $user = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 1,
        'opens_at' => null,
        'closes_at' => null,
        'is_closed' => true,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    $slots = app(AvailableBookingSlots::class)->get(
        user: $user,
        serviceId: $service->id,
        date: Carbon::parse('2026-10-05'),
    );

    expect($slots)->toBeEmpty();
});

it('does not return slots for a day without business hours', function () {
    $user = User::factory()->create();

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    $slots = app(AvailableBookingSlots::class)->get(
        user: $user,
        serviceId: $service->id,
        date: Carbon::parse('2026-10-05'),
    );

    expect($slots)->toBeEmpty();
});

it('does not return a slot that would extend beyond closing time', function () {
    $this->travelTo(Carbon::parse('2026-10-05 08:00:00'));

    $user = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 1,
        'opens_at' => '09:00:00',
        'closes_at' => '10:30:00',
        'is_closed' => false,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $slots = app(AvailableBookingSlots::class)->get(
        user: $user,
        serviceId: $service->id,
        date: Carbon::parse('2026-10-05'),
    );

    expect($slots)
        ->toHaveCount(1)
        ->and($slots[0]['starts_at']->format('H:i'))->toBe('09:00')
        ->and($slots[0]['ends_at']->format('H:i'))->toBe('10:00');
});

it('does not return a slot that overlaps an occupying booking', function (BookingStatus $status) {
    $user = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 1,
        'opens_at' => '09:00:00',
        'closes_at' => '12:00:00',
        'is_closed' => false,
    ]);

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
        'starts_at' => '2026-10-05 10:00:00',
        'ends_at' => '2026-10-05 11:00:00',
        'status' => $status,
    ]);

    $slots = app(AvailableBookingSlots::class)->get(
        user: $user,
        serviceId: $service->id,
        date: Carbon::parse('2026-10-05'),
    );

    expect(
        $slots->contains(
            fn($slot) => $slot['starts_at']->format('H:i') === '10:00'
        )
    )->toBeFalse();
})->with([
    BookingStatus::PENDING,
    BookingStatus::CONFIRMED,
]);

it('allows a slot when the overlapping booking does not occupy the slot', function (BookingStatus $status) {
    $this->travelTo(Carbon::parse('2026-10-05 08:00:00'));

    $user = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 1,
        'opens_at' => '09:00:00',
        'closes_at' => '12:00:00',
        'is_closed' => false,
    ]);

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
        'starts_at' => '2026-10-05 10:00:00',
        'ends_at' => '2026-10-05 11:00:00',
        'status' => $status,
    ]);

    $slots = app(AvailableBookingSlots::class)->get(
        user: $user,
        serviceId: $service->id,
        date: Carbon::parse('2026-10-05'),
    );

    expect(
        $slots->contains(
            fn($slot) => $slot['starts_at']->format('H:i') === '10:00'
        )
    )->toBeTrue();
})->with([
    BookingStatus::CANCELLED,
    BookingStatus::COMPLETED,
]);

it('allows a slot adjacent to an existing booking', function () {
    $this->travelTo(Carbon::parse('2026-10-05 08:00:00'));

    $user = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 1,
        'opens_at' => '09:00:00',
        'closes_at' => '12:00:00',
        'is_closed' => false,
    ]);

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
        'starts_at' => '2026-10-05 10:00:00',
        'ends_at' => '2026-10-05 11:00:00',
        'status' => BookingStatus::CONFIRMED,
    ]);

    $slots = app(AvailableBookingSlots::class)->get(
        user: $user,
        serviceId: $service->id,
        date: Carbon::parse('2026-10-05'),
    );

    expect(
        $slots->contains(
            fn($slot) => $slot['starts_at']->format('H:i') === '11:00'
        )
    )->toBeTrue();
});

it('does not return past slots for today', function () {
    $this->travelTo(Carbon::parse('2026-10-05 10:30:00'));

    $user = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 1,
        'opens_at' => '09:00:00',
        'closes_at' => '13:00:00',
        'is_closed' => false,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $slots = app(AvailableBookingSlots::class)->get(
        user: $user,
        serviceId: $service->id,
        date: Carbon::parse('2026-10-05'),
    );

    expect($slots)
        ->toHaveCount(2)
        ->and($slots->first()['starts_at']->format('H:i'))->toBe('11:00')
        ->and($slots->last()['starts_at']->format('H:i'))->toBe('12:00');

    $this->travelBack();
});

it('cannot generate availability for an inactive service', function () {
    $user = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 1,
        'opens_at' => '09:00:00',
        'closes_at' => '17:00:00',
        'is_closed' => false,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'is_active' => false,
    ]);

    app(AvailableBookingSlots::class)->get(
        user: $user,
        serviceId: $service->id,
        date: Carbon::parse('2026-10-05'),
    );
})->throws(ModelNotFoundException::class);

it('allows the current booking slot when that booking is excluded', function () {
    $user = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 1,
        'opens_at' => '09:00:00',
        'closes_at' => '12:00:00',
        'is_closed' => false,
    ]);

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
        'starts_at' => '2026-10-12 10:00:00',
        'ends_at' => '2026-10-12 11:00:00',
        'status' => BookingStatus::CONFIRMED,
    ]);

    $slots = app(AvailableBookingSlots::class)->get(
        user: $user,
        serviceId: $service->id,
        date: Carbon::parse('2026-10-12'),
        exceptBookingId: $booking->id,
    );

    expect(
        $slots->contains(
            fn($slot) => $slot['starts_at']->format('H:i') === '10:00'
        )
    )->toBeTrue();
});

it('returns no slots when the service is longer than business hours', function () {
    $this->travelTo(Carbon::parse('2026-10-05 08:00:00'));

    $user = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 1,
        'opens_at' => '09:00:00',
        'closes_at' => '10:00:00',
        'is_closed' => false,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 120,
        'is_active' => true,
    ]);

    $slots = app(AvailableBookingSlots::class)->get(
        user: $user,
        serviceId: $service->id,
        date: Carbon::parse('2026-10-05'),
    );

    expect($slots)->toBeEmpty();
});
