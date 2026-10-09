<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays upcoming bookings for the authenticated user', function () {
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

    $startsAt = now()->addDay()->setTime(14, 0);
    $endsAt = $startsAt->copy()->addHour();

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'status' => BookingStatus::CONFIRMED,
    ]);

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->assertStatus(200)
        ->assertSee($customer->name)
        ->assertSee($service->name)
        ->assertSee($startsAt->format('d.m.Y. H:i'))
        ->assertSee($booking->status->value);
});

it('does not display bookings belonging to another user', function () {
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

    $startsAt = now()->addDay()->setTime(14, 0);
    $endsAt = $startsAt->copy()->addHour();

    Booking::factory()->create([
        'user_id' => $otherUser->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'status' => BookingStatus::CONFIRMED,
    ]);

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->assertDontSee($customer->name)
        ->assertDontSee($service->name);
});

it('displays an empty state when there are no upcoming bookings', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->assertStatus(200)
        ->assertSee('No upcoming bookings.');
});

it('displays dashboard summary counts for the authenticated user', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');

    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create();
    $service = Service::factory()->for($user)->create();

    Customer::factory()->for($user)->count(2)->create();

    Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-20 14:00:00',
        'ends_at' => '2026-10-20 15:00:00',
        'status' => BookingStatus::PENDING,
    ]);

    Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-21 10:00:00',
        'ends_at' => '2026-10-21 11:00:00',
        'status' => BookingStatus::CONFIRMED,
    ]);

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->assertSee('Today')
        ->assertSee('1')
        ->assertSee('Upcoming')
        ->assertSee('1')
        ->assertSee('Pending')
        ->assertSee('1')
        ->assertSee('Customers')
        ->assertSee('3');
});

it('displays todays bookings for the authenticated user', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');

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

    $startsAt = now()->setTime(14, 0);
    $endsAt = $startsAt->copy()->addHour();

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'status' => BookingStatus::CONFIRMED,
    ]);

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->assertSeeHtml("Today's bookings")
        ->assertSee($customer->name)
        ->assertSee($service->name)
        ->assertSee($startsAt->format('d.m.Y. H:i'));
});

it('displays an empty state when there are no bookings today', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->assertStatus(200)
        ->assertSeeHtml("Today's bookings")
        ->assertSee('No bookings today.')
        ->assertSeeHtml("You don't have any bookings scheduled for today.");
});

it('displays quick action links', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->assertSeeHtml(
            'href="' . route('bookings.create') . '"'
        )
        ->assertSee('New booking')
        ->assertSeeHtml(
            'href="' . route('customers.create') . '"'
        )
        ->assertSee('New customer')
        ->assertSeeHtml(
            'href="' . route('services.create') . '"'
        )
        ->assertSee('New service')
        ->assertSeeHtml(
            'href="' . route('bookings.calendar') . '"'
        )
        ->assertSee('View calendar');
});
