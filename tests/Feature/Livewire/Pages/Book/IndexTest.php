<?php

use App\Actions\Bookings\CreateBooking;
use App\Models\BusinessHour;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays the business by slug', function () {
    $business = User::factory()->create([
        'name' => 'Demo Business',
        'slug' => 'demo-business',
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->assertSuccessful()
        ->assertSee('Demo Business');
});

it('returns a 404 for an unknown business slug', function () {
    Livewire::test('pages::book.index', [
        'business' => 'does-not-exist',
    ])
        ->assertNotFound();
});

it('displays active services for the business', function () {
    $business = User::factory()->create([
        'name' => 'Demo Business',
        'slug' => 'demo-business',
    ]);

    $activeService = Service::factory()->create([
        'user_id' => $business->id,
        'name' => 'Consultation',
        'is_active' => true,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->assertSuccessful()
        ->assertSee($activeService->name);
});

it('does not display inactive services', function () {
    $business = User::factory()->create([
        'name' => 'Demo Business',
        'slug' => 'demo-business',
    ]);

    $inactiveService = Service::factory()->create([
        'user_id' => $business->id,
        'name' => 'Inactive Service',
        'is_active' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->assertSuccessful()
        ->assertDontSee($inactiveService->name);
});

it('does not display services belonging to another business', function () {
    $business = User::factory()->create([
        'name' => 'Demo Business',
        'slug' => 'demo-business',
    ]);

    $otherBusiness = User::factory()->create([
        'name' => 'Other Business',
        'slug' => 'other-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $otherBusiness->id,
        'name' => 'Other Business Service',
        'is_active' => true,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->assertSuccessful()
        ->assertDontSee($service->name);
});

it('can select an active service', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'name' => 'Consultation',
        'is_active' => true,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->assertSet('selectedServiceId', $service->id);
});

it('cannot select an inactive service', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'is_active' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->assertSet('selectedServiceId', null);
});

it('cannot select a service belonging to another business', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $otherBusiness = User::factory()->create([
        'slug' => 'other-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $otherBusiness->id,
        'is_active' => true,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->assertSet('selectedServiceId', null);
});

it('can select a date after selecting a service', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'is_active' => true,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->call('selectDate', '2026-10-01')
        ->assertSet('selectedDate', '2026-10-01');
});

it('cannot select a date before selecting a service', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectDate', '2026-10-01')
        ->assertSet('selectedDate', null);
});

it('loads available slots after selecting a service and date', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $date = now()->addDay()->startOfDay();

    $business->businessHours()->create([
        'day_of_week' => $date->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->call('selectDate', $date->toDateString())
        ->assertSet('selectedDate', $date->toDateString())
        ->assertCount('availableSlots', 3);
});

it('clears available slots when no service is selected', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectDate', now()->addDay()->toDateString())
        ->assertSet('availableSlots', []);
});

it('can select an available slot', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $date = now()->addDay()->startOfDay();

    $business->businessHours()->create([
        'day_of_week' => $date->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->call('selectDate', $date->toDateString())
        ->call('selectSlot', '09:00')
        ->assertSet('selectedStartAt', "{$date->toDateString()} 09:00:00")
        ->assertSet('selectedEndAt', "{$date->toDateString()} 10:00:00");
});

it('cannot select a slot that is not available', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $date = now()->addDay()->startOfDay();

    $business->businessHours()->create([
        'day_of_week' => $date->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->call('selectDate', $date->toDateString())
        ->call('selectSlot', '13:00')
        ->assertSet('selectedStartAt', null)
        ->assertSet('selectedEndAt', null);
});

it('can create a booking', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $date = now()->addDay()->startOfDay();

    $business->businessHours()->create([
        'day_of_week' => $date->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->call('selectDate', $date->toDateString())
        ->call('selectSlot', '09:00')
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->set('customerNotes', 'First appointment.')
        ->call('book')
        ->assertSet('bookingConfirmed', true);

    $customer = $business->customers()
        ->where('email', 'john@example.com')
        ->first();

    expect($customer)->not->toBeNull();

    $this->assertDatabaseHas('bookings', [
        'user_id' => $business->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => $date->copy()->setTime(9, 0)->toDateTimeString(),
        'ends_at' => $date->copy()->setTime(10, 0)->toDateTimeString(),
        'status' => 'pending',
    ]);
});

it('requires customer details before creating a booking', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $date = now()->addDay()->startOfDay();

    $business->businessHours()->create([
        'day_of_week' => $date->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->call('selectDate', $date->toDateString())
        ->call('selectSlot', '09:00')
        ->call('book')
        ->assertHasErrors([
            'customerName',
            'customerEmail',
            'customerPhone',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('requires a valid customer email before creating a booking', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->set('selectedStartAt', now()->addDay()->setTime(9, 0)->toDateTimeString())
        ->set('selectedEndAt', now()->addDay()->setTime(10, 0)->toDateTimeString())
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'not-an-email')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors([
            'customerEmail',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('rejects a customer name longer than 255 characters', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->set('selectedStartAt', now()->addDay()->setTime(9, 0)->toDateTimeString())
        ->set('selectedEndAt', now()->addDay()->setTime(10, 0)->toDateTimeString())
        ->set('customerName', str_repeat('A', 256))
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors([
            'customerName',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('rejects a customer phone longer than 50 characters', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->set('selectedStartAt', now()->addDay()->setTime(9, 0)->toDateTimeString())
        ->set('selectedEndAt', now()->addDay()->setTime(10, 0)->toDateTimeString())
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', str_repeat('1', 51))
        ->call('book')
        ->assertHasErrors([
            'customerPhone',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('requires a valid booking start date', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->set('selectedStartAt', 'not-a-date')
        ->set('selectedEndAt', now()->addDay()->setTime(10, 0)->toDateTimeString())
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors([
            'selectedStartAt',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('requires a valid booking end date', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->set('selectedStartAt', now()->addDay()->setTime(9, 0)->toDateTimeString())
        ->set('selectedEndAt', 'not-a-date')
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors([
            'selectedEndAt',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('rejects a booking for a valid time range that is not an available slot', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    BusinessHour::factory()->create([
        'user_id' => $business->id,
        'day_of_week' => now()->addDay()->dayOfWeek,
        'opens_at' => '09:00:00',
        'closes_at' => '17:00:00',
        'is_closed' => false,
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $startAt = now()->addDay()->setTime(9, 30);
    $endAt = $startAt->copy()->addHour();

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->set('selectedStartAt', $startAt->toDateTimeString())
        ->set('selectedEndAt', $endAt->toDateTimeString())
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors([
            'booking',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('rejects a booking for an inactive service', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => false,
    ]);

    $startAt = now()->addDay()->setTime(10, 0);
    $endAt = $startAt->copy()->addHour();

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->set('selectedServiceId', $service->id)
        ->set('selectedStartAt', $startAt->toDateTimeString())
        ->set('selectedEndAt', $endAt->toDateTimeString())
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors([
            'booking',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('rejects a booking outside business hours', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    BusinessHour::factory()->create([
        'user_id' => $business->id,
        'day_of_week' => now()->addDay()->dayOfWeek,
        'opens_at' => '09:00:00',
        'closes_at' => '17:00:00',
        'is_closed' => false,
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $startAt = now()->addDay()->setTime(16, 30);
    $endAt = $startAt->copy()->addHour();

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->set('selectedServiceId', $service->id)
        ->set('selectedStartAt', $startAt->toDateTimeString())
        ->set('selectedEndAt', $endAt->toDateTimeString())
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors([
            'booking',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('rejects a booking on a closed business day', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    BusinessHour::factory()->create([
        'user_id' => $business->id,
        'day_of_week' => now()->addDay()->dayOfWeek,
        'opens_at' => '09:00:00',
        'closes_at' => '17:00:00',
        'is_closed' => true,
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $startAt = now()->addDay()->setTime(10, 0);
    $endAt = $startAt->copy()->addHour();

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->set('selectedServiceId', $service->id)
        ->set('selectedStartAt', $startAt->toDateTimeString())
        ->set('selectedEndAt', $endAt->toDateTimeString())
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors([
            'booking',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('shows a confirmation after successfully creating a booking', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $date = now()->addDay()->startOfDay();

    $business->businessHours()->create([
        'day_of_week' => $date->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->call('selectDate', $date->toDateString())
        ->call('selectSlot', '09:00')
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->set('customerNotes', 'First appointment.')
        ->call('book')
        ->assertSet('bookingConfirmed', true);
});

it('shows the booked service and time in the confirmation', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'name' => 'Consultation',
        'duration' => 60,
        'is_active' => true,
    ]);

    $date = now()->addDay()->startOfDay();

    $business->businessHours()->create([
        'day_of_week' => $date->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->call('selectDate', $date->toDateString())
        ->call('selectSlot', '09:00')
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertSet('bookingConfirmed', true)
        ->assertSet('confirmationServiceName', 'Consultation')
        ->assertSet('confirmationDate', $date->format('M j, Y'))
        ->assertSet('confirmationStartTime', '09:00')
        ->assertSet('confirmationEndTime', '10:00')
        ->assertSee('Consultation')
        ->assertSee($date->format('M j, Y'))
        ->assertSee('09:00')
        ->assertSee('10:00');
});

it('shows an error when the selected slot is booked before submission', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $date = now()->addDay()->startOfDay();

    $business->businessHours()->create([
        'day_of_week' => $date->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    $visitor = Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->call('selectDate', $date->toDateString())
        ->call('selectSlot', '09:00')
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->set('customerNotes', 'First appointment.');

    $existingCustomer = $business->customers()->create([
        'name' => 'Another Customer',
        'email' => 'another@example.com',
        'phone' => '+381 60 999 999',
    ]);

    $createBooking = app(CreateBooking::class);

    $createBooking->create(
        user: $business,
        customerId: $existingCustomer->id,
        serviceId: $service->id,
        data: [
            'starts_at' => $date->copy()->setTime(9, 0)->toDateTimeString(),
            'ends_at' => $date->copy()->setTime(10, 0)->toDateTimeString(),
        ],
    );

    $visitor
        ->call('book')
        ->assertHasErrors([
            'booking' => 'This time slot is no longer available. Please choose another time.',
        ])
        ->assertSet('bookingConfirmed', false);

    $this->assertDatabaseMissing('customers', [
        'user_id' => $business->id,
        'email' => 'john@example.com',
    ]);

    $this->assertDatabaseCount('bookings', 1);
});

it('requires a service before creating a booking', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors([
            'selectedServiceId',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('requires a time slot before creating a booking', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors([
            'selectedStartAt',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('requires a booking end time before creating a booking', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->set('selectedStartAt', now()->addDay()->setTime(9, 0)->toDateTimeString())
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors([
            'selectedEndAt',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('requires the booking end time to be after the start time', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $startAt = now()->addDay()->setTime(10, 0);
    $endAt = $startAt->copy()->subHour();

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->set('selectedStartAt', $startAt->toDateTimeString())
        ->set('selectedEndAt', $endAt->toDateTimeString())
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors([
            'selectedEndAt',
        ]);

    $this->assertDatabaseCount('bookings', 0);
});

it('rejects a booking for a slot that is not available', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $date = now()->addDay()->startOfDay();

    $business->businessHours()->create([
        'day_of_week' => $date->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->call('selectDate', $date->toDateString())
        ->set(
            'selectedStartAt',
            $date->copy()->setTime(10, 30)->toDateTimeString(),
        )
        ->set(
            'selectedEndAt',
            $date->copy()->setTime(11, 30)->toDateTimeString(),
        )
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors([
            'booking' => 'This time slot is no longer available. Please choose another time.',
        ])
        ->assertSet('bookingConfirmed', false);

    $this->assertDatabaseMissing('customers', [
        'user_id' => $business->id,
        'email' => 'john@example.com',
    ]);

    $this->assertDatabaseCount('bookings', 0);
});


it('clears the selected slot when the date changes', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $date = now()->addDay()->startOfDay();
    $anotherDate = $date->copy()->addWeek();

    $business->businessHours()->create([
        'day_of_week' => $date->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->call('selectDate', $date->toDateString())
        ->call('selectSlot', '09:00')
        ->assertSet('selectedStartAt', $date->copy()->setTime(9, 0)->toDateTimeString())
        ->call('selectDate', $anotherDate->toDateString())
        ->assertSet('selectedStartAt', null)
        ->assertSet('selectedEndAt', null);
});

it('clears the selected slot when the service changes', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $firstService = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $secondService = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 30,
        'is_active' => true,
    ]);

    $date = now()->addDay()->startOfDay();

    $business->businessHours()->create([
        'day_of_week' => $date->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $firstService->id)
        ->call('selectDate', $date->toDateString())
        ->call('selectSlot', '09:00')
        ->assertSet('selectedStartAt', $date->copy()->setTime(9, 0)->toDateTimeString())
        ->call('selectService', $secondService->id)
        ->assertSet('selectedServiceId', $secondService->id)
        ->assertSet('selectedStartAt', null)
        ->assertSet('selectedEndAt', null);
});

it('keeps no selected slot when an unavailable slot is selected', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $business->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $date = now()->addDay()->startOfDay();

    $business->businessHours()->create([
        'day_of_week' => $date->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->call('selectService', $service->id)
        ->call('selectDate', $date->toDateString())
        ->call('selectSlot', '09:00')
        ->assertSet('selectedStartAt', $date->copy()->setTime(9, 0)->toDateTimeString())
        ->call('selectSlot', '15:00')
        ->assertSet('selectedStartAt', null)
        ->assertSet('selectedEndAt', null);
});

it('cannot create a booking for a service belonging to another business', function () {
    $business = User::factory()->create([
        'slug' => 'demo-business',
    ]);

    $otherBusiness = User::factory()->create([
        'slug' => 'other-business',
    ]);

    $service = Service::factory()->create([
        'user_id' => $otherBusiness->id,
        'duration' => 60,
        'is_active' => true,
    ]);

    $date = now()->addDay()->startOfDay();

    $business->businessHours()->create([
        'day_of_week' => $date->dayOfWeek,
        'opens_at' => '09:00',
        'closes_at' => '12:00',
        'is_closed' => false,
    ]);

    Livewire::test('pages::book.index', [
        'business' => $business->slug,
    ])
        ->set('selectedServiceId', $service->id)
        ->set('selectedStartAt', $date->copy()->setTime(9, 0)->toDateTimeString())
        ->set('selectedEndAt', $date->copy()->setTime(10, 0)->toDateTimeString())
        ->set('customerName', 'John Doe')
        ->set('customerEmail', 'john@example.com')
        ->set('customerPhone', '+381 60 123 456')
        ->call('book')
        ->assertHasErrors('booking')
        ->assertSet('bookingConfirmed', false);

    $this->assertDatabaseCount('bookings', 0);

    $this->assertDatabaseMissing('customers', [
        'user_id' => $business->id,
        'email' => 'john@example.com',
    ]);
});
