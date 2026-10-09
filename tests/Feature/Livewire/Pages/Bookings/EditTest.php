<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays the existing booking data', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Doe',
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'Consultation',
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
        'notes' => 'Important booking.',
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.edit', [
            'bookingId' => $booking->id,
        ])
        ->assertStatus(200)
        ->assertSet('customerId', $customer->id)
        ->assertSet('serviceId', $service->id)
        ->assertSet('startsAt', '2026-10-01T14:00')
        ->assertSet('endsAt', '2026-10-01T15:00')
        ->assertSet('notes', 'Important booking.')
        ->assertSee('Edit booking')
        ->assertSee('John Doe')
        ->assertSee('Consultation');
});

it('does not display customers or services belonging to another user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Customer',
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Service',
        'is_active' => true,
    ]);

    $otherUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Customer::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Customer',
    ]);

    Service::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Service',
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.edit', [
            'bookingId' => $booking->id,
        ])
        ->assertSee('My Customer')
        ->assertSee('My Service')
        ->assertDontSee('Other Customer')
        ->assertDontSee('Other Service');
});

it('does not display inactive services', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $activeService = Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'Active Service',
        'is_active' => true,
    ]);

    Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'Inactive Service',
        'is_active' => false,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $activeService->id,
        'status' => BookingStatus::PENDING,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.edit', [
            'bookingId' => $booking->id,
        ])
        ->assertSee('Active Service')
        ->assertDontSee('Inactive Service');
});

it('cannot edit a booking belonging to another user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $otherUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

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

    $this->actingAs($user)
        ->get(route('bookings.edit', [
            'bookingId' => $booking->id,
        ]))
        ->assertNotFound();
});

it('validates required fields', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

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

    Livewire::actingAs($user)
        ->test('pages::bookings.edit', [
            'bookingId' => $booking->id,
        ])
        ->set('customerId', null)
        ->set('serviceId', null)
        ->set('startsAt', '')
        ->set('endsAt', '')
        ->call('save')
        ->assertHasErrors([
            'customerId' => ['required'],
            'serviceId' => ['required'],
            'startsAt' => ['required'],
            'endsAt' => ['required'],
        ]);
});

it('validates that the booking ends after it starts', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

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

    Livewire::actingAs($user)
        ->test('pages::bookings.edit', [
            'bookingId' => $booking->id,
        ])
        ->set('startsAt', '2026-10-01T15:00')
        ->set('endsAt', '2026-10-01T14:00')
        ->call('save')
        ->assertHasErrors([
            'endsAt' => ['after'],
        ]);
});

it('updates a booking and redirects to the bookings index', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

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
        'name' => 'Consultation',
        'duration' => 60,
        'is_active' => true,
    ]);

    $newService = Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'Follow-up',
        'duration' => 90,
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 14:00:00',
        'ends_at' => '2026-10-01 15:00:00',
        'status' => BookingStatus::CONFIRMED,
        'notes' => 'Old notes.',
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.edit', [
            'bookingId' => $booking->id,
        ])
        ->set('customerId', $newCustomer->id)
        ->set('serviceId', $newService->id)
        ->set('startsAt', '2026-10-01T16:00')
        ->set('endsAt', '2026-10-01T17:30')
        ->set('notes', 'Updated notes.')
        ->call('save')
        ->assertRedirectToRoute('bookings.index');

    $updatedBooking = $booking->fresh();

    expect($updatedBooking)
        ->customer_id->toBe($newCustomer->id)
        ->service_id->toBe($newService->id)
        ->starts_at->format('Y-m-d H:i:s')->toBe('2026-10-01 16:00:00')
        ->ends_at->format('Y-m-d H:i:s')->toBe('2026-10-01 17:30:00')
        ->status->toBe(BookingStatus::CONFIRMED)
        ->notes->toBe('Updated notes.');
});

it('displays a booking error when the new time slot is already booked', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
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

    Livewire::actingAs($user)
        ->test('pages::bookings.edit', [
            'bookingId' => $booking->id,
        ])
        ->set('startsAt', '2026-10-01T16:30')
        ->set('endsAt', '2026-10-01T17:30')
        ->call('save')
        ->assertHasErrors([
            'booking' => 'This time slot is no longer available. Please choose another time.',
        ])
        ->assertNoRedirect();

    expect($booking->fresh()->starts_at->format('Y-m-d H:i:s'))
        ->toBe('2026-10-01 14:00:00');
});

it('redirects to the booking show page after updating when opened from the show page', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create();

    $service = Service::factory()
        ->for($user)
        ->create([
            'duration' => 60,
        ]);

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-05 14:00:00',
            'ends_at' => '2026-10-05 15:00:00',
        ]);

    $this->actingAs($user);

    Livewire::test('pages::bookings.edit', [
        'bookingId' => $booking->id,
        'returnRoute' => 'bookings.show',
    ])
        ->set('startsAt', '2026-10-05T16:00')
        ->set('endsAt', '2026-10-05T17:00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirectToRoute('bookings.show', [
            'bookingId' => $booking->id,
        ]);
});

it('loads the return route from the query parameters', function () {

    $user = User::factory()->create();

    $booking = Booking::factory()
        ->for($user)
        ->create();

    $this->actingAs($user);

    Livewire::withQueryParams([
        'returnRoute' => 'bookings.show',
    ])
        ->test('pages::bookings.edit', [
            'bookingId' => $booking->id,
        ])
        ->assertSet('returnRoute', 'bookings.show');
});

it('redirects to the booking show page when cancelling from the show page', function () {
    $user = User::factory()->create();

    $booking = Booking::factory()
        ->for($user)
        ->create();

    $this->actingAs($user);

    Livewire::test('pages::bookings.edit', [
        'bookingId' => $booking->id,
        'returnRoute' => 'bookings.show',
    ])
        ->call('cancel')
        ->assertRedirectToRoute('bookings.show', [
            'bookingId' => $booking->id,
        ]);
});

it('redirects to the booking index when cancelling', function () {
    $user = User::factory()->create();

    $booking = Booking::factory()
        ->for($user)
        ->create();

    $this->actingAs($user);

    Livewire::test('pages::bookings.edit', [
        'bookingId' => $booking->id,
    ])
        ->call('cancel')
        ->assertRedirectToRoute('bookings.index');
});

it('displays a cancel button', function () {
    $user = User::factory()->create();

    $booking = Booking::factory()
        ->for($user)
        ->create();

    $this->actingAs($user);

    Livewire::test('pages::bookings.edit', [
        'bookingId' => $booking->id,
    ])
        ->assertSeeHtml('wire:click="cancel"')
        ->assertSee('Cancel');
});

it('sets the end time from the selected service duration', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()
        ->for($user)
        ->create();

    $service = Service::factory()
        ->for($user)
        ->create([
            'duration' => 90,
            'is_active' => true,
        ]);

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-05 14:00:00',
            'ends_at' => '2026-10-05 15:30:00',
        ]);

    $this->actingAs($user);

    Livewire::test('pages::bookings.edit', [
        'bookingId' => $booking->id,
    ])
        ->set('serviceId', $service->id)
        ->set('startsAt', '2026-10-05T16:00')
        ->assertSet('endsAt', '2026-10-05T17:30');
});

it('updates the end time when the service changes', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()
        ->for($user)
        ->create();

    $service = Service::factory()
        ->for($user)
        ->create([
            'duration' => 30,
            'is_active' => true,
        ]);

    $newService = Service::factory()
        ->for($user)
        ->create([
            'duration' => 90,
            'is_active' => true,
        ]);

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-05 14:00:00',
            'ends_at' => '2026-10-05 14:30:00',
        ]);

    $this->actingAs($user);

    Livewire::test('pages::bookings.edit', [
        'bookingId' => $booking->id,
    ])
        ->assertSet('startsAt', '2026-10-05T14:00')
        ->assertSet('endsAt', '2026-10-05T14:30')
        ->set('serviceId', $newService->id)
        ->assertSet('endsAt', '2026-10-05T15:30');
});

it('clears the end time when the selected service is cleared', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()
        ->for($user)
        ->create();

    $service = Service::factory()
        ->for($user)
        ->create([
            'duration' => 30,
            'is_active' => true,
        ]);

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-05 16:00:00',
            'ends_at' => '2026-10-05 16:30:00',
        ]);

    $this->actingAs($user);

    Livewire::test('pages::bookings.edit', [
        'bookingId' => $booking->id,
    ])
        ->assertSet('endsAt', '2026-10-05T16:30')
        ->set('serviceId', '')
        ->assertSet('endsAt', '');
});

it('redirects to the calendar after updating when opened from the calendar', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
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
        'starts_at' => '2026-10-21 14:00:00',
        'ends_at' => '2026-10-21 15:00:00',
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.edit', [
            'bookingId' => $booking->id,
            'returnRoute' => 'bookings.calendar',
        ])
        ->set('startsAt', '2026-10-21T15:00')
        ->set('endsAt', '2026-10-21T16:00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirectToRoute('bookings.calendar');
});

it('redirects to the calendar when cancelling an edit opened from the calendar', function () {
    $user = User::factory()->create();

    $booking = Booking::factory()
        ->for($user)
        ->create();

    Livewire::actingAs($user)
        ->test('pages::bookings.edit', [
            'bookingId' => $booking->id,
            'returnRoute' => 'bookings.calendar',
        ])
        ->call('cancel')
        ->assertRedirectToRoute('bookings.calendar');
});
