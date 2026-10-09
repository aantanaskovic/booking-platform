<?php

use App\Actions\Bookings\CancelBooking;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;

it('builds a booking cancelled email', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->for($user)->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $service = Service::factory()->for($user)->create([
        'name' => 'Haircut',
        'duration' => 60,
        'price' => 25,
    ]);

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
        ]);

    $notification = new BookingCancelledNotification($booking);

    $mail = $notification->toMail($customer);

    expect($mail)
        ->toBeInstanceOf(MailMessage::class)
        ->subject->toBe('Booking cancelled')
        ->greeting->toBe('Hello John Doe')
        ->introLines->toContain('Your booking has been cancelled.')
        ->introLines->toContain('Service: Haircut')
        ->introLines->toContain('Date: ' . $booking->starts_at->format('F j, Y'))
        ->introLines->toContain(
            'Time: ' . $booking->starts_at->format('g:i A')
                . ' - '
                . $booking->ends_at->format('g:i A')
        );
});

it('sends a cancellation notification to the customer', function () {
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
            'status' => BookingStatus::PENDING,
        ]);

    $action = app(CancelBooking::class);

    $action->cancel($user, $booking->id);

    Notification::assertSentTo(
        $customer,
        BookingCancelledNotification::class,
    );
});
