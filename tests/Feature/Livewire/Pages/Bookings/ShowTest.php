<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Livewire\Livewire;

it('displays the booking for the authenticated user', function () {
    $user = User::factory()->create();

    $booking = Booking::factory()
        ->for($user)
        ->create();

    $this->actingAs($user);

    Livewire::test('pages::bookings.show', [
        'bookingId' => $booking->id,
    ])
        ->assertSuccessful();
});

it('displays the booking details', function () {
    $user = User::factory()->create();

    $booking = Booking::factory()
        ->for($user)
        ->create([
            'starts_at' => '2026-10-05 14:00:00',
            'ends_at' => '2026-10-05 15:00:00',
            'status' => 'confirmed',
            'notes' => 'Customer requested a quiet room.',
        ]);

    $this->actingAs($user);

    Livewire::test('pages::bookings.show', [
        'bookingId' => $booking->id,
    ])
        ->assertSee($booking->customer->name)
        ->assertSee($booking->service->name)
        ->assertSee('Confirmed')
        ->assertSee('October 5, 2026')
        ->assertSee('14:00')
        ->assertSee('15:00')
        ->assertSee('Customer requested a quiet room.');
});

it('does not display a booking belonging to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $booking = Booking::factory()
        ->for($otherUser)
        ->create();

    $this->actingAs($user);

    Livewire::test('pages::bookings.show', [
        'bookingId' => $booking->id,
    ])->assertNotFound();
});

it('displays the booking when it has no notes', function () {
    $user = User::factory()->create();

    $booking = Booking::factory()
        ->for($user)
        ->create([
            'notes' => null,
        ]);

    $this->actingAs($user);

    Livewire::test('pages::bookings.show', [
        'bookingId' => $booking->id,
    ])
        ->assertSuccessful()
        ->assertSee($booking->customer->name)
        ->assertSee($booking->service->name);
});

it('updates the booking status through the appropriate action', function (
    string $method,
    BookingStatus $initialStatus,
    BookingStatus $expectedStatus,
) {
    $user = User::factory()->create();

    $booking = Booking::factory()
        ->for($user)
        ->create([
            'status' => $initialStatus,
        ]);

    $this->actingAs($user);

    Livewire::test('pages::bookings.show', [
        'bookingId' => $booking->id,
    ])
        ->call($method)
        ->assertSuccessful();

    expect($booking->fresh()->status)
        ->toBe($expectedStatus);
})->with([
    'confirm' => [
        'confirm',
        BookingStatus::PENDING,
        BookingStatus::CONFIRMED,
    ],
    'cancel pending booking' => [
        'cancel',
        BookingStatus::PENDING,
        BookingStatus::CANCELLED,
    ],
    'complete confirmed booking' => [
        'complete',
        BookingStatus::CONFIRMED,
        BookingStatus::COMPLETED,
    ],
    'cancel confirmed booking' => [
        'cancel',
        BookingStatus::CONFIRMED,
        BookingStatus::CANCELLED,
    ],
]);

it('displays the appropriate status actions', function (
    BookingStatus $status,
    array $visibleActions,
    array $hiddenActions,
) {
    $user = User::factory()->create();

    $booking = Booking::factory()
        ->for($user)
        ->create([
            'status' => $status,
        ]);

    $this->actingAs($user);

    $component = Livewire::test('pages::bookings.show', [
        'bookingId' => $booking->id,
    ]);

    foreach ($visibleActions as $action) {
        $component->assertSeeHtml(
            'data-booking-action="' . $action . '"'
        );
    }

    foreach ($hiddenActions as $action) {
        $component->assertDontSeeHtml(
            'data-booking-action="' . $action . '"'
        );
    }
})->with([
    'pending' => [
        BookingStatus::PENDING,
        ['confirm', 'cancel'],
        ['complete'],
    ],
    'confirmed' => [
        BookingStatus::CONFIRMED,
        ['cancel', 'complete'],
        ['confirm'],
    ],
    'cancelled' => [
        BookingStatus::CANCELLED,
        [],
        ['confirm', 'cancel', 'complete'],
    ],
    'completed' => [
        BookingStatus::COMPLETED,
        [],
        ['confirm', 'cancel', 'complete'],
    ],
]);

it('displays a link to edit the booking and return to the show page', function () {
    $user = User::factory()->create();

    $booking = Booking::factory()
        ->for($user)
        ->create();

    $this->actingAs($user);

    $editUrl = route('bookings.edit', [
        'bookingId' => $booking->id,
        'returnRoute' => 'bookings.show',
    ]);

    Livewire::test('pages::bookings.show', [
        'bookingId' => $booking->id,
    ])
        ->assertSeeHtml('href="' . $editUrl . '"')
        ->assertSee('Edit booking');
});
