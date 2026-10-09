<?php

use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

function createBookingManagementUrl(
    Booking $booking,
    Customer $customer,
    ?DateTimeInterface $expiration = null,
): string {
    return URL::temporarySignedRoute(
        'bookings.manage',
        $expiration ?? now()->addHours(24),
        [
            'bookingId' => $booking->id,
            'customerId' => $customer->id,
        ],
    );
}

it('allows access with a valid signed url for the booking customer', function () {
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
        ->create();

    $url = createBookingManagementUrl($booking, $customer);

    $this->get($url)->assertOk();
});

it('denies access when the signed url contains another customer id', function () {
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

    $url = createBookingManagementUrl($booking, $otherCustomer);

    $this->get($url)->assertForbidden();
});

it('denies access when the signed url has expired', function () {
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
        ->create();

    $url = createBookingManagementUrl(
        $booking,
        $customer,
        now()->subMinute(),
    );

    $this->get($url)->assertForbidden();
});

it('displays the booking details', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create([
            'name' => 'Petar Petrovic',
        ]);

    $service = Service::factory()
        ->for($user)
        ->create([
            'name' => 'Haircut',
        ]);

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create();

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.manage', [
            'bookingId' => $booking->id,
        ])
        ->assertSee('Manage your booking')
        ->assertSee('Petar Petrovic')
        ->assertSee('Haircut')
        ->assertSee($booking->status->value);
});

it('shows the reschedule option for a pending booking', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.manage', [
            'bookingId' => $booking->id,
        ])
        ->assertSee('Reschedule booking');
});

it('opens the reschedule form when requested', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.manage', [
            'bookingId' => $booking->id,
        ])
        ->call('startRescheduling')
        ->assertSet('rescheduling', true)
        ->assertSee('Choose a new date')
        ->assertSee('Choose a new time');
});

it('shows available slots for the selected reschedule date', function () {
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

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.manage', [
            'bookingId' => $booking->id,
        ])
        ->set('rescheduling', true)
        ->set('selectedDate', '2026-10-05')
        ->assertSee('09:00')
        ->assertSee('10:00')
        ->assertSee('11:00');
});

it('reschedules the booking to a selected available slot', function () {
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

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.manage', [
            'bookingId' => $booking->id,
        ])
        ->set('selectedDate', '2026-10-05')
        ->set('selectedSlot', '2026-10-05 11:00:00')
        ->call('reschedule')
        ->assertHasNoErrors()
        ->assertSee('Booking rescheduled successfully.');

    expect($booking->fresh()->starts_at->toDateTimeString())
        ->toBe('2026-10-05 11:00:00')
        ->and($booking->fresh()->ends_at->toDateTimeString())
        ->toBe('2026-10-05 12:00:00');
});

it('rejects rescheduling when the selected slot is no longer available', function () {
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

    $component = Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])->test('pages::bookings.manage', [
        'bookingId' => $booking->id,
    ])
        ->set('selectedDate', '2026-10-05')
        ->assertSet(
            'availableSlots',
            fn($slots) =>
            collect($slots)->contains(
                fn($slot) => $slot['starts_at'] === '2026-10-05 11:00:00'
            )
        );

    $anotherCustomer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $anotherCustomer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-05 11:00:00',
        'ends_at' => '2026-10-05 12:00:00',
        'status' => BookingStatus::CONFIRMED,
    ]);

    $component
        ->set('selectedSlot', '2026-10-05 11:00:00')
        ->call('reschedule')
        ->assertHasErrors(['selectedSlot']);

    expect($booking->fresh()->starts_at->toDateTimeString())
        ->toBe('2026-10-05 10:00:00')
        ->and($booking->fresh()->ends_at->toDateTimeString())
        ->toBe('2026-10-05 11:00:00');
});

it('does not offer rescheduling for cancelled or completed bookings', function (BookingStatus $status) {
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
        'starts_at' => now()->addDays(2)->setTime(10, 0),
        'ends_at' => now()->addDays(2)->setTime(11, 0),
        'status' => $status,
    ]);

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.manage', [
            'bookingId' => $booking->id,
        ])
        ->assertDontSee('Reschedule booking');
})->with([
    'cancelled' => BookingStatus::CANCELLED,
    'completed' => BookingStatus::COMPLETED,
]);

it('does not allow rescheduling a cancelled booking', function () {
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
        'status' => BookingStatus::CANCELLED,
    ]);

    expect(fn() => Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.manage', [
            'bookingId' => $booking->id,
        ])
        ->set('rescheduling', true)
        ->set('selectedDate', '2026-10-05')
        ->set('selectedSlot', '2026-10-05 11:00:00')
        ->call('reschedule'))
        ->toThrow(
            BookingException::class,
            'Only pending or confirmed bookings can be edited.'
        );
});

it('does not allow rescheduling a booking to a past date', function () {
    $this->travelTo(Carbon::parse('2026-10-05 08:00:00'));

    $user = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 4,
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

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.manage', [
            'bookingId' => $booking->id,
        ])
        ->set('selectedDate', '2026-10-04')
        ->set('selectedSlot', '2026-10-04 10:00:00')
        ->call('reschedule')
        ->assertHasErrors(['selectedDate']);

    expect($booking->fresh()->starts_at->toDateTimeString())
        ->toBe('2026-10-05 10:00:00')
        ->and($booking->fresh()->ends_at->toDateTimeString())
        ->toBe('2026-10-05 11:00:00');
});

it('requires a date when rescheduling a booking', function () {
    $this->travelTo(Carbon::parse('2026-10-05 08:00:00'));

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
        'starts_at' => '2026-10-05 10:00:00',
        'ends_at' => '2026-10-05 11:00:00',
        'status' => BookingStatus::CONFIRMED,
    ]);

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.manage', [
            'bookingId' => $booking->id,
        ])
        ->set('selectedSlot', '2026-10-06 10:00:00')
        ->call('reschedule')
        ->assertHasErrors([
            'selectedDate' => 'required',
        ]);

    expect($booking->fresh()->starts_at->toDateTimeString())
        ->toBe('2026-10-05 10:00:00')
        ->and($booking->fresh()->ends_at->toDateTimeString())
        ->toBe('2026-10-05 11:00:00');
});

it('rejects a management url when the booking id is tampered with', function () {
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
        ->create();

    $url = createBookingManagementUrl($booking, $customer);

    $tamperedUrl = str_replace(
        "/bookings/{$booking->id}/manage",
        '/bookings/' . ($booking->id + 1) . '/manage',
        $url,
    );

    expect($tamperedUrl)->not->toBe($url);

    $this->get($tamperedUrl)->assertForbidden();
});
