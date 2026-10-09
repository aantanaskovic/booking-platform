<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays the create booking form', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Doe',
    ]);

    Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'Consultation',
        'is_active' => true,
    ]);

    Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Service',
        'is_active' => false,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.create')
        ->assertStatus(200)
        ->assertSee('Create booking')
        ->assertSee('John Doe')
        ->assertSee('Consultation')
        ->assertDontSee('Old Service');
});

it('does not display customers or services belonging to another user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Customer',
    ]);

    Service::factory()->create([
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

    Livewire::actingAs($user)
        ->test('pages::bookings.create')
        ->assertSee('My Customer')
        ->assertSee('My Service')
        ->assertDontSee('Other Customer')
        ->assertDontSee('Other Service');
});

it('validates required fields', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.create')
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

    Livewire::actingAs($user)
        ->test('pages::bookings.create')
        ->set('customerId', $customer->id)
        ->set('serviceId', $service->id)
        ->set('startsAt', '2026-10-01T15:00')
        ->set('endsAt', '2026-10-01T14:00')
        ->call('save')
        ->assertHasErrors([
            'endsAt' => ['after'],
        ]);

    expect(Booking::count())->toBe(0);
});

it('creates a booking and redirects to the bookings index', function () {
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

    Livewire::actingAs($user)
        ->test('pages::bookings.create')
        ->set('customerId', $customer->id)
        ->set('serviceId', $service->id)
        ->set('startsAt', '2026-10-01T14:00')
        ->set('endsAt', '2026-10-01T15:00')
        ->set('notes', 'Customer requested a quiet room.')
        ->call('save')
        ->assertRedirectToRoute('dashboard');

    $booking = Booking::query()->first();

    expect($booking)
        ->not->toBeNull()
        ->and($booking->user_id)->toBe($user->id)
        ->and($booking->customer_id)->toBe($customer->id)
        ->and($booking->service_id)->toBe($service->id)
        ->and($booking->status)->toBe(BookingStatus::PENDING)
        ->and($booking->notes)->toBe('Customer requested a quiet room.');
});

it('redirects to the calendar after creating a booking when opened from the calendar', function () {
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

    Livewire::withQueryParams([
        'returnRoute' => 'bookings.calendar',
    ])
        ->actingAs($user)
        ->test('pages::bookings.create')
        ->set('customerId', $customer->id)
        ->set('serviceId', $service->id)
        ->set('startsAt', '2026-10-01T14:00')
        ->set('endsAt', '2026-10-01T15:00')
        ->call('save')
        ->assertRedirectToRoute('bookings.calendar');
});

it('displays a booking error when the selected time slot is already booked', function () {
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

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 14:00:00',
        'ends_at' => '2026-10-01 15:00:00',
        'status' => BookingStatus::CONFIRMED,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.create')
        ->set('customerId', $customer->id)
        ->set('serviceId', $service->id)
        ->set('startsAt', '2026-10-01T14:30')
        ->set('endsAt', '2026-10-01T15:30')
        ->call('save')
        ->assertHasErrors([
            'booking' => 'This time slot is no longer available. Please choose another time.',
        ])
        ->assertNoRedirect();

    expect(Booking::count())->toBe(1);
});

it('allows an adjacent booking', function () {
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

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 14:00:00',
        'ends_at' => '2026-10-01 15:00:00',
        'status' => BookingStatus::CONFIRMED,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.create')
        ->set('customerId', $customer->id)
        ->set('serviceId', $service->id)
        ->set('startsAt', '2026-10-01T15:00')
        ->set('endsAt', '2026-10-01T16:00')
        ->call('save')
        ->assertRedirectToRoute('dashboard');

    $this->assertDatabaseHas('bookings', [
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-01 15:00:00',
        'ends_at' => '2026-10-01 16:00:00',
    ]);
});

it('loads the booking date from the url', function () {
    $user = User::factory()->create();

    Livewire::withQueryParams([
        'date' => '2026-10-15',
    ])
        ->actingAs($user)
        ->test('pages::bookings.create')
        ->assertSet('startsAt', '2026-10-15T09:00')
        ->assertSet('endsAt', '');
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

    $this->actingAs($user);

    Livewire::test('pages::bookings.create')
        ->set('customerId', $customer->id)
        ->set('serviceId', $service->id)
        ->set('startsAt', '2026-10-05T16:00')
        ->assertSet('endsAt', '2026-10-05T17:30');
});

it('updates the end time when the service changes', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

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

    $this->actingAs($user);

    Livewire::test('pages::bookings.create')
        ->set('serviceId', $service->id)
        ->set('startsAt', '2026-10-05T16:00')
        ->assertSet('endsAt', '2026-10-05T16:30')
        ->set('serviceId', $newService->id)
        ->assertSet('endsAt', '2026-10-05T17:30');
});

it('clears the end time when the selected service is cleared', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $service = Service::factory()
        ->for($user)
        ->create([
            'duration' => 30,
            'is_active' => true,
        ]);

    $this->actingAs($user);

    Livewire::test('pages::bookings.create')
        ->set('serviceId', $service->id)
        ->set('startsAt', '2026-10-05T16:00')
        ->assertSet('endsAt', '2026-10-05T16:30')
        ->set('serviceId', '')
        ->assertSet('endsAt', '');
});

it('redirects to the calendar when cancelling a booking opened from the calendar', function (string $returnRoute) {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->withQueryParams([
            'returnRoute' => $returnRoute,
        ])
        ->test('pages::bookings.create')
        ->call('cancel')
        ->assertRedirectToRoute($returnRoute);
})->with([
    'bookings.calendar',
    'bookings.index',
]);
