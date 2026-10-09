<?php

use App\Actions\Bookings\ReschedulePublicBooking;
use App\Actions\Bookings\UpdateBooking;
use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Exceptions\Bookings\BookingSlotUnavailableException;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;

it('reschedules a booking belonging to the customer', function () {
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

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-05 10:00:00',
        'ends_at' => '2026-10-05 11:00:00',
        'status' => BookingStatus::CONFIRMED,
    ]);

    $rescheduledBooking = app(ReschedulePublicBooking::class)->reschedule(
        customer: $customer,
        bookingId: $booking->id,
        startsAt: Carbon::parse('2026-10-05 11:00:00'),
        endsAt: Carbon::parse('2026-10-05 12:00:00'),
    );

    expect($rescheduledBooking->starts_at->toDateTimeString())
        ->toBe('2026-10-05 11:00:00')
        ->and($rescheduledBooking->ends_at->toDateTimeString())
        ->toBe('2026-10-05 12:00:00');

    expect($booking->fresh()->starts_at->toDateTimeString())
        ->toBe('2026-10-05 11:00:00');
});

it('does not allow another customer to reschedule the booking', function () {
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
        ->create();

    expect(fn() => app(ReschedulePublicBooking::class)->reschedule(
        customer: $otherCustomer,
        bookingId: $booking->id,
        startsAt: Carbon::parse('2026-10-10 11:00:00'),
        endsAt: Carbon::parse('2026-10-10 11:30:00'),
    ))->toThrow(BookingException::class);
});

it('preserves the booking notes when rescheduling', function () {
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

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-05 10:00:00',
        'ends_at' => '2026-10-05 11:00:00',
        'status' => BookingStatus::CONFIRMED,
        'notes' => 'Customer requested a quiet room.',
    ]);

    $rescheduledBooking = app(ReschedulePublicBooking::class)->reschedule(
        customer: $customer,
        bookingId: $booking->id,
        startsAt: Carbon::parse('2026-10-05 11:00:00'),
        endsAt: Carbon::parse('2026-10-05 12:00:00'),
    );

    expect($rescheduledBooking->starts_at->toDateTimeString())
        ->toBe('2026-10-05 11:00:00')
        ->and($rescheduledBooking->ends_at->toDateTimeString())
        ->toBe('2026-10-05 12:00:00')
        ->and($booking->fresh()->notes)
        ->toBe('Customer requested a quiet room.');
});

it('does not allow rescheduling a booking that cannot be edited', function (BookingStatus $status) {
    $user = User::factory()->create();
    $customer = Customer::factory()->for($user)->create();
    $service = Service::factory()->for($user)->create();

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'status' => $status,
            'starts_at' => '2026-10-10 10:00:00',
            'ends_at' => '2026-10-10 10:30:00',
        ]);

    expect(fn() => app(ReschedulePublicBooking::class)->reschedule(
        customer: $customer,
        bookingId: $booking->id,
        startsAt: Carbon::parse('2026-10-10 11:00:00'),
        endsAt: Carbon::parse('2026-10-10 11:30:00'),
    ))->toThrow(
        BookingException::class,
        'Only pending or confirmed bookings can be edited.'
    );

    expect($booking->fresh()->starts_at->toDateTimeString())
        ->toBe('2026-10-10 10:00:00')
        ->and($booking->fresh()->ends_at->toDateTimeString())
        ->toBe('2026-10-10 10:30:00')
        ->and($booking->fresh()->status)
        ->toBe($status);
})->with([
    'cancelled booking' => BookingStatus::CANCELLED,
    'completed booking' => BookingStatus::COMPLETED,
]);

it('does not allow rescheduling a booking when its service is inactive', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->for($user)->create();
    $service = Service::factory()->for($user)->create([
        'is_active' => true,
    ]);

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-10 10:00:00',
            'ends_at' => '2026-10-10 10:30:00',
        ]);

    $service->update(['is_active' => false]);

    expect(fn() => app(ReschedulePublicBooking::class)->reschedule(
        customer: $customer,
        bookingId: $booking->id,
        startsAt: Carbon::parse('2026-10-10 11:00:00'),
        endsAt: Carbon::parse('2026-10-10 11:30:00'),
    ))->toThrow(
        BookingException::class,
        'The selected service is currently inactive.'
    );

    expect($booking->fresh()->starts_at->toDateTimeString())
        ->toBe('2026-10-10 10:00:00')
        ->and($booking->fresh()->ends_at->toDateTimeString())
        ->toBe('2026-10-10 10:30:00');
});

it('rejects rescheduling outside business hours', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create();

    $service = Service::factory()->for($user)->create([
        'duration' => 30,
        'is_active' => true,
    ]);

    BusinessHour::factory()->for($user)->create([
        'day_of_week' => 6,
        'opens_at' => '09:00:00',
        'closes_at' => '12:00:00',
        'is_closed' => false,
    ]);

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-10 10:00:00',
            'ends_at' => '2026-10-10 10:30:00',
        ]);

    expect(fn() => app(ReschedulePublicBooking::class)->reschedule(
        customer: $customer,
        bookingId: $booking->id,
        startsAt: Carbon::parse('2026-10-10 08:00:00'),
        endsAt: Carbon::parse('2026-10-10 08:30:00'),
    ))->toThrow(BookingSlotUnavailableException::class);

    expect($booking->fresh()->starts_at->toDateTimeString())
        ->toBe('2026-10-10 10:00:00');
});

it('rejects rescheduling beyond closing time', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create();

    $service = Service::factory()->for($user)->create([
        'duration' => 30,
        'is_active' => true,
    ]);

    BusinessHour::factory()->for($user)->create([
        'day_of_week' => 6,
        'opens_at' => '09:00:00',
        'closes_at' => '12:00:00',
        'is_closed' => false,
    ]);

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-10 10:00:00',
            'ends_at' => '2026-10-10 10:30:00',
        ]);

    expect(fn() => app(ReschedulePublicBooking::class)->reschedule(
        customer: $customer,
        bookingId: $booking->id,
        startsAt: Carbon::parse('2026-10-10 11:45:00'),
        endsAt: Carbon::parse('2026-10-10 12:15:00'),
    ))->toThrow(BookingSlotUnavailableException::class);

    expect($booking->fresh()->starts_at->toDateTimeString())
        ->toBe('2026-10-10 10:00:00');
});
