<?php

use App\Actions\Bookings\GenerateBookingCancellationUrl;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

it('allows access to the cancellation page with a valid signed url', function () {
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

    $url = URL::temporarySignedRoute(
        'bookings.cancel',
        now()->addHours(24),
        [
            'bookingId' => $booking->id,
            'customerId' => $customer->id,
        ],
    );

    $this->get($url)
        ->assertOk();
});

it('includes the customer in the cancellation url', function () {
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

    $url = app(GenerateBookingCancellationUrl::class)->generate(
        $booking,
        $customer,
    );

    $request = request()->create($url);

    expect($request->query('customerId'))
        ->toBe((string) $customer->id);

    expect(URL::hasValidSignature($request))
        ->toBeTrue();
});

it('rejects a cancellation url when the customer does not match the booking', function () {
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

    $url = URL::temporarySignedRoute(
        'bookings.cancel',
        now()->addHours(24),
        [
            'bookingId' => $booking->id,
            'customerId' => $otherCustomer->id,
        ],
    );

    $this->get($url)
        ->assertForbidden();
});

it('rejects an expired cancellation url', function () {
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

    $url = URL::temporarySignedRoute(
        'bookings.cancel',
        now()->addHour(),
        [
            'bookingId' => $booking->id,
            'customerId' => $customer->id,
        ],
    );

    Carbon::setTestNow(now()->addHours(2));

    $this->get($url)
        ->assertForbidden();
});

it('displays the booking details', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create([
            'name' => 'John Doe',
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
        ->create([
            'starts_at' => now()->addDay()->setTime(14, 0),
            'ends_at' => now()->addDay()->setTime(15, 0),
        ]);

    $url = URL::temporarySignedRoute(
        'bookings.cancel',
        now()->addHours(24),
        [
            'bookingId' => $booking->id,
            'customerId' => $customer->id,
        ],
    );

    $this->get($url)
        ->assertOk()
        ->assertSee('Haircut')
        ->assertSee('John Doe')
        ->assertSee(
            $booking->starts_at->format('F j, Y')
        )
        ->assertSee(
            $booking->starts_at->format('g:i A')
        )
        ->assertSee(
            $booking->ends_at->format('g:i A')
        );
});

it('allows the customer to cancel the booking', function () {
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

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.cancel', [
            'bookingId' => $booking->id,
        ])
        ->call('cancel');

    expect($booking->fresh()->status)
        ->toBe(BookingStatus::CANCELLED);
});

it('displays the cancel booking button for a cancellable booking', function () {
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
            'status' => BookingStatus::CONFIRMED,
        ]);

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.cancel', [
            'bookingId' => $booking->id,
        ])
        ->assertSee('Cancel booking');
});

it('shows a cancellation confirmation before cancelling the booking', function () {
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
            'status' => BookingStatus::CONFIRMED,
        ]);

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.cancel', [
            'bookingId' => $booking->id,
        ])
        ->call('confirmCancellation')
        ->assertSee('Are you sure you want to cancel this booking?')
        ->assertSee('Confirm cancellation')
        ->assertSee('Keep booking');

    expect($booking->fresh()->status)
        ->toBe(BookingStatus::CONFIRMED);
});

it('keeps the booking when the customer cancels the cancellation confirmation', function () {
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
            'status' => BookingStatus::CONFIRMED,
        ]);

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.cancel', [
            'bookingId' => $booking->id,
        ])
        ->call('confirmCancellation')
        ->set('confirmingCancellation', false)
        ->assertSee('Cancel booking')
        ->assertDontSee('Are you sure you want to cancel this booking?');

    expect($booking->fresh()->status)
        ->toBe(BookingStatus::CONFIRMED);
});

it('shows that the booking has been cancelled after cancellation', function () {
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
            'status' => BookingStatus::CONFIRMED,
        ]);

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.cancel', [
            'bookingId' => $booking->id,
        ])
        ->call('confirmCancellation')
        ->call('cancel')
        ->assertSee('Booking cancelled')
        ->assertDontSee('Confirm cancellation');

    expect($booking->fresh()->status)
        ->toBe(BookingStatus::CANCELLED);
});

it('does not display the cancel button for a non-cancellable booking', function (BookingStatus $status) {
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

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.cancel', [
            'bookingId' => $booking->id,
        ])
        ->assertDontSee('Cancel booking');
})->with([
    BookingStatus::CANCELLED,
    BookingStatus::COMPLETED,
]);

it('allows the customer to cancel a confirmed booking', function () {
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
            'status' => BookingStatus::CONFIRMED,
        ]);

    Livewire::withQueryParams([
        'customerId' => $customer->id,
    ])
        ->test('pages::bookings.cancel', [
            'bookingId' => $booking->id,
        ])
        ->call('confirmCancellation')
        ->call('cancel');

    expect($booking->fresh()->status)
        ->toBe(BookingStatus::CANCELLED);
});

it('rejects a cancellation url when the booking id is tampered with', function () {
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

    $url = URL::temporarySignedRoute(
        'bookings.cancel',
        now()->addHours(24),
        [
            'bookingId' => $booking->id,
            'customerId' => $customer->id,
        ],
    );

    $tamperedUrl = str_replace(
        "/bookings/{$booking->id}/cancel",
        '/bookings/' . ($booking->id + 1) . '/cancel',
        $url,
    );

    expect($tamperedUrl)->not->toBe($url);

    $this->get($tamperedUrl)->assertForbidden();
});
