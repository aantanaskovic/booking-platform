<?php

use App\Enums\BookingDateFilter;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays bookings for the authenticated user', function () {
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
        'price' => 50,
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => now()->addDay()->setTime(14, 0),
        'ends_at' => now()->addDay()->setTime(15, 0),
        'status' => BookingStatus::PENDING,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->assertSee('John Doe')
        ->assertSee('Consultation')
        ->assertSee('50,00')
        ->assertSee('Pending');
});

it('does not display bookings belonging to another user', function () {
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
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
    ]);

    $otherUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $otherCustomer = Customer::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Customer',
    ]);

    $otherService = Service::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Service',
    ]);

    Booking::factory()->create([
        'user_id' => $otherUser->id,
        'customer_id' => $otherCustomer->id,
        'service_id' => $otherService->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->assertSee('My Customer')
        ->assertSee('My Service')
        ->assertDontSee('Other Customer')
        ->assertDontSee('Other Service');
});

it('displays an empty state when there are no bookings', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->assertSee('No bookings yet.')
        ->assertSee('Create your first booking to get started.');
});

it('displays bookings with different statuses', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
    ]);

    foreach (BookingStatus::cases() as $status) {
        Booking::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'starts_at' => now()->addDays(1),
            'ends_at' => now()->addDays(1)->addHour(),
            'status' => $status,
        ]);
    }

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->assertSee('Pending')
        ->assertSee('Confirmed')
        ->assertSee('Cancelled')
        ->assertSee('Completed');
});

it('confirms a pending booking', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->call('confirm', $booking->id)
        ->assertSee('Confirmed');

    expect($booking->fresh()->status)
        ->toBe(BookingStatus::CONFIRMED);
});

it('cancels a pending booking', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->call('cancel', $booking->id)
        ->assertSee('Cancelled');

    expect($booking->fresh()->status)
        ->toBe(BookingStatus::CANCELLED);
});

it('completes a confirmed booking', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->call('complete', $booking->id)
        ->assertSee('Completed');

    expect($booking->fresh()->status)
        ->toBe(BookingStatus::COMPLETED);
});

it('displays an edit link for editable bookings', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->assertSee(
            route('bookings.edit', $booking->id),
            escape: false
        );
});

it('filters bookings by status', function () {
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
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    $confirmedCustomer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'Jane Doe',
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $confirmedCustomer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->set('statusFilter', BookingStatus::PENDING)
        ->assertSee('John Doe')
        ->assertDontSee('Jane Doe');
});

it('filters bookings by status from the filter buttons', function () {
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
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    $confirmedCustomer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'Jane Doe',
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $confirmedCustomer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->call('filterByStatus', BookingStatus::PENDING)
        ->assertSet('statusFilter', BookingStatus::PENDING)
        ->assertSee('John Doe')
        ->assertDontSee('Jane Doe');
});

it('displays booking status filter buttons', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->assertSee('All')
        ->assertSee('Pending')
        ->assertSee('Confirmed')
        ->assertSee('Cancelled')
        ->assertSee('Completed');
});

it('filters bookings when the status filter changes', function () {
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
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    $confirmedCustomer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'Jane Doe',
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $confirmedCustomer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->set('statusFilter', BookingStatus::PENDING)
        ->assertSet('statusFilter', BookingStatus::PENDING)
        ->assertSee('John Doe')
        ->assertDontSee('Jane Doe');
});

it('connects the pending filter button to the status filter', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->assertSeeHtml(
            'wire:click="$set(\'statusFilter\', \'pending\')"'
        );
});

it('shows all bookings when the status filter is cleared', function () {
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
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    $confirmedCustomer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'Jane Doe',
    ]);

    Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $confirmedCustomer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::CONFIRMED,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->set('statusFilter', BookingStatus::PENDING)
        ->assertSee('John Doe')
        ->assertDontSee('Jane Doe')
        ->set('statusFilter', null)
        ->assertSee('John Doe')
        ->assertSee('Jane Doe');
});

it('marks the correct booking status filter as active', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.index')
        ->assertSeeHtml('data-filter="all"')
        ->assertSeeHtml('aria-pressed="true"')
        ->set('statusFilter', BookingStatus::PENDING)
        ->assertSeeHtml('data-filter="pending"')
        ->assertSeeHtml('aria-pressed="true"');
});

it('filters bookings when date filter changes', function () {
    Carbon::setTestNow('2026-09-30 12:00');

    $user = User::factory()->create();

    $todayBooking = Booking::factory()->for($user)->create([
        'starts_at' => '2026-09-30 14:00',
        'ends_at' => '2026-09-30 15:00',
    ]);

    $upcomingBooking = Booking::factory()->for($user)->create([
        'starts_at' => '2026-10-01 14:00',
        'ends_at' => '2026-10-01 15:00',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::bookings.index')
        ->set('dateFilter', BookingDateFilter::TODAY)
        ->assertSee($todayBooking->customer->name)
        ->assertDontSee($upcomingBooking->customer->name);

    Carbon::setTestNow();
});

it('displays booking date filter buttons', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::bookings.index')
        ->assertSee('All')
        ->assertSee('Today')
        ->assertSee('Upcoming')
        ->assertSee('Past');
});

it('connects date filter buttons to the date filter', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::bookings.index')
        ->assertSee('data-date-filter="all"', escape: false)
        ->assertSee('data-date-filter="today"', escape: false)
        ->assertSee('data-date-filter="upcoming"', escape: false)
        ->assertSee('data-date-filter="past"', escape: false);
});

it('marks the selected booking date filter as active', function (BookingDateFilter $filter) {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::bookings.index')
        ->set('dateFilter', $filter)
        ->assertSeeHtml('aria-pressed="true"');
})->with([
    'today' => BookingDateFilter::TODAY,
    'upcoming' => BookingDateFilter::UPCOMING,
    'past' => BookingDateFilter::PAST,
]);

it('marks all booking date filter as active by default', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::bookings.index')
        ->assertSeeHtml('data-date-filter="all"')
        ->assertSeeHtml('aria-pressed="true"');
});
