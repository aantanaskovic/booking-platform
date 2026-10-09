<?php

use App\Actions\Bookings\SendBookingReminders;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingReminderNotification;
use Illuminate\Support\Facades\Notification;

it('sends reminders for bookings starting within 24 hours', function () {
    Notification::fake();

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
            'starts_at' => now()->addHours(12),
            'ends_at' => now()->addHours(13),
            'status' => BookingStatus::CONFIRMED,
            'reminder_sent_at' => null,
        ]);

    app(SendBookingReminders::class)->send();

    Notification::assertSentTo(
        $customer,
        BookingReminderNotification::class,
    );

    expect($booking->fresh()->reminder_sent_at)->not->toBeNull();
});

it('does not send reminders for bookings that are not eligible', function (
    array $bookingData,
) {
    Notification::fake();

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
        ->create($bookingData);

    app(SendBookingReminders::class)->send();

    Notification::assertNothingSent();
})->with([
    'more than 24 hours away' => [
        'bookingData' => [
            'starts_at' => now()->addHours(25),
            'ends_at' => now()->addHours(26),
            'status' => BookingStatus::CONFIRMED,
            'reminder_sent_at' => null,
        ],
    ],

    'already started' => [
        'bookingData' => [
            'starts_at' => now()->subHour(),
            'ends_at' => now(),
            'status' => BookingStatus::CONFIRMED,
            'reminder_sent_at' => null,
        ],
    ],

    'cancelled booking' => [
        'bookingData' => [
            'starts_at' => now()->addHours(12),
            'ends_at' => now()->addHours(13),
            'status' => BookingStatus::CANCELLED,
            'reminder_sent_at' => null,
        ],
    ],

    'completed booking' => [
        'bookingData' => [
            'starts_at' => now()->addHours(12),
            'ends_at' => now()->addHours(13),
            'status' => BookingStatus::COMPLETED,
            'reminder_sent_at' => null,
        ],
    ],

    'reminder already sent' => [
        'bookingData' => [
            'starts_at' => now()->addHours(12),
            'ends_at' => now()->addHours(13),
            'status' => BookingStatus::CONFIRMED,
            'reminder_sent_at' => now()->subHour(),
        ],
    ],
]);

it('does not send the same reminder more than once', function () {
    Notification::fake();

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
            'starts_at' => now()->addHours(12),
            'ends_at' => now()->addHours(13),
            'status' => BookingStatus::CONFIRMED,
            'reminder_sent_at' => null,
        ]);

    $action = app(SendBookingReminders::class);

    $action->send();
    $action->send();

    Notification::assertSentToTimes(
        $customer,
        BookingReminderNotification::class,
        1,
    );

    expect($booking->fresh()->reminder_sent_at)->not->toBeNull();
});
