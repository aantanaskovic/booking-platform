<?php

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingReminderNotification;
use Illuminate\Notifications\Messages\MailMessage;

it('builds a booking reminder email', function () {
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
            'starts_at' => now()->addHours(12),
            'ends_at' => now()->addHours(13),
        ]);

    $notification = new BookingReminderNotification($booking);

    $mail = $notification->toMail($customer);

    expect($mail)
        ->toBeInstanceOf(MailMessage::class)
        ->subject->toBe('Booking reminder')
        ->greeting->toBe('Hello John Doe')
        ->introLines->toContain('This is a reminder that you have an upcoming booking.')
        ->introLines->toContain('Service: Haircut')
        ->introLines->toContain(
            'Date: ' . $booking->starts_at->format('F j, Y')
        )
        ->introLines->toContain(
            'Time: ' . $booking->starts_at->format('g:i A')
                . ' - '
                . $booking->ends_at->format('g:i A')
        );
});
