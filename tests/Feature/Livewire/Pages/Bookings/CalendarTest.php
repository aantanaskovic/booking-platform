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

it('displays bookings for the authenticated user in the current month', function () {
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
            'starts_at' => '2026-10-15 10:00:00',
            'ends_at' => '2026-10-15 11:00:00',
        ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertSee('John Doe')
        ->assertSee('Haircut')
        ->assertSee($booking->starts_at->format('10:00'));
});

it('does not display bookings belonging to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $customer = Customer::factory()
        ->for($otherUser)
        ->create([
            'name' => 'Other Customer',
        ]);

    $service = Service::factory()
        ->for($otherUser)
        ->create([
            'name' => 'Other Service',
        ]);

    Booking::factory()
        ->for($otherUser)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-15 10:00:00',
            'ends_at' => '2026-10-15 11:00:00',
        ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertDontSee('Other Customer')
        ->assertDontSee('Other Service');
});

it('does not display bookings outside the selected month', function () {
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

    Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-11-15 10:00:00',
            'ends_at' => '2026-11-15 11:00:00',
        ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertDontSee('John Doe')
        ->assertDontSee('Haircut');
});

it('displays bookings for the next month', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create([
            'name' => 'November Customer',
        ]);

    $service = Service::factory()
        ->for($user)
        ->create([
            'name' => 'November Service',
        ]);

    Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-11-15 10:00:00',
            'ends_at' => '2026-11-15 11:00:00',
        ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->call('nextMonth')
        ->assertSet('year', 2026)
        ->assertSet('month', 11)
        ->assertSee('November Customer')
        ->assertSee('November Service')
        ->assertSee('10:00');
});

it('displays bookings for the previous month', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create([
            'name' => 'December Customer',
        ]);

    $service = Service::factory()
        ->for($user)
        ->create([
            'name' => 'December Service',
        ]);

    Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-12-15 10:00:00',
            'ends_at' => '2026-12-15 11:00:00',
        ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.calendar', [
            'year' => 2027,
            'month' => 1,
        ])
        ->call('previousMonth')
        ->assertSet('year', 2026)
        ->assertSet('month', 12)
        ->assertSee('December Customer')
        ->assertSee('December Service')
        ->assertSee('10:00');
});

it('returns to the current month', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 12,
        ])
        ->call('goToCurrentMonth')
        ->assertSet('year', 2026)
        ->assertSet('month', 10);

    Carbon::setTestNow();
});

it('builds the calendar grid', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertSet('calendarDays', function ($days) {
            return count($days) === 35;
        });
});

it('starts the calendar grid on the Monday before the selected month', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertSet('calendarDays', function ($days) {
            return Carbon::parse($days[0]['date'])->isSameDay(
                Carbon::parse('2026-09-28')
            );
        });
});

it('ends the calendar grid on the Sunday after the selected month', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertSet('calendarDays', function ($days) {
            return Carbon::parse($days[array_key_last($days)]['date'])
                ->isSameDay(Carbon::parse('2026-11-01'));
        });
});

it('displays the days of the week', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertSee('Mon')
        ->assertSee('Tue')
        ->assertSee('Wed')
        ->assertSee('Thu')
        ->assertSee('Fri')
        ->assertSee('Sat')
        ->assertSee('Sun');
});

it('renders a data attribute for each calendar day', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertSeeHtml('data-date="2026-09-28"')
        ->assertSeeHtml('data-date="2026-10-01"')
        ->assertSeeHtml('data-date="2026-10-31"')
        ->assertSeeHtml('data-date="2026-11-01"');
});

it('displays a booking inside its calendar day', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create([
        'name' => 'John Doe',
    ]);

    $service = Service::factory()->for($user)->create([
        'name' => 'Consultation',
    ]);

    Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-15 10:00:00',
            'ends_at' => '2026-10-15 11:00:00',
        ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertSeeHtml('data-date="2026-10-15"')
        ->assertSee('John Doe');
});

it('renders a booking inside its matching calendar day', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create([
        'name' => 'John Doe',
    ]);

    $service = Service::factory()->for($user)->create([
        'name' => 'Consultation',
    ]);

    Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-15 10:00:00',
            'ends_at' => '2026-10-15 11:00:00',
        ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertSeeHtml('data-date="2026-10-15"')
        ->assertSee('John Doe')
        ->assertSee('Consultation')
        ->assertSee('10:00');
});

it('links each booking to its show page', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create();

    $service = Service::factory()->for($user)->create();

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => '2026-10-15 10:00:00',
            'ends_at' => '2026-10-15 11:00:00',
        ]);

    $showUrl = route('bookings.show', [
        'bookingId' => $booking->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertSeeHtml('href="' . $showUrl . '"');
});

it('returns to the current month when today is clicked', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 12,
        ])
        ->call('goToCurrentMonth')
        ->assertSet('year', 2026)
        ->assertSet('month', 10);
});

it('displays booking status with the appropriate visual style', function (
    BookingStatus $status,
    string $expectedClass,
) {
    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create([
        'name' => 'John Doe',
    ]);

    $service = Service::factory()->for($user)->create([
        'name' => 'Consultation',
    ]);

    $booking = Booking::factory()->for($user)->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => '2026-10-15 10:00:00',
        'ends_at' => '2026-10-15 11:00:00',
        'status' => $status,
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertSeeHtml(
            'href="' . route('bookings.show', ['bookingId' => $booking->id]) . '"'
        )
        ->assertSeeHtml($expectedClass);
})->with([
    'pending' => [
        BookingStatus::PENDING,
        'bg-yellow-50',
    ],
    'confirmed' => [
        BookingStatus::CONFIRMED,
        'bg-indigo-50',
    ],
    'cancelled' => [
        BookingStatus::CANCELLED,
        'bg-gray-100',
    ],
    'completed' => [
        BookingStatus::COMPLETED,
        'bg-gray-100',
    ],
]);

it('loads the selected month from the url', function () {
    $user = User::factory()->create();

    Livewire::withQueryParams([
        'year' => 2026,
        'month' => 12,
    ])
        ->actingAs($user)
        ->test('pages::bookings.calendar')
        ->assertSet('year', 2026)
        ->assertSet('month', 12);
});

it('navigates to the next month', function () {
    $user = User::factory()->create();

    Livewire::withQueryParams([
        'year' => 2026,
        'month' => 10,
    ])
        ->actingAs($user)
        ->test('pages::bookings.calendar')
        ->call('nextMonth')
        ->assertSet('year', 2026)
        ->assertSet('month', 11);
});

it('provides an add booking link for today and future dates', function (
    string $date,
) {
    Carbon::setTestNow('2026-10-20 12:00:00');

    $user = User::factory()->create();

    $url = e(route('bookings.create', [
        'date' => $date,
        'returnRoute' => 'bookings.calendar',
    ]));

    Livewire::actingAs($user)
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertSeeHtml(
            'href="' . $url . '"'
        );
})->with([
    'today' => '2026-10-20',
    'future date' => '2026-10-25',
]);

it('does not provide an add booking link for past dates', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');

    $user = User::factory()->create();

    $url = route('bookings.create', [
        'date' => '2026-10-15',
    ]);

    Livewire::actingAs($user)
        ->test('pages::bookings.calendar', [
            'year' => 2026,
            'month' => 10,
        ])
        ->assertDontSeeHtml(
            'href="' . $url . '"'
        );
});
