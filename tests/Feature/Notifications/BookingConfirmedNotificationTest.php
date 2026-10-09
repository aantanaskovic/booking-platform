<?php

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Notifications\Messages\MailMessage;

it('builds a booking confirmed email', function () {
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

    $notification = new BookingConfirmedNotification($booking);

    $mail = $notification->toMail($customer);

    expect($mail)
        ->toBeInstanceOf(MailMessage::class)
        ->subject->toBe('Booking confirmed')
        ->greeting->toBe('Hello John Doe')
        ->introLines->toContain('Your booking has been confirmed.')
        ->introLines->toContain('Service: Haircut')
        ->introLines->toContain('Date: ' . $booking->starts_at->format('F j, Y'))
        ->introLines->toContain(
            'Time: ' . $booking->starts_at->format('g:i A')
                . ' - '
                . $booking->ends_at->format('g:i A')
        );
});
