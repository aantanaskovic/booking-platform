<?php

use App\Actions\Bookings\GenerateBookingCancellationUrl;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingCancellationRequestedNotification;
use Illuminate\Notifications\Messages\MailMessage;

it('builds a cancellation request email with a cancellation url', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
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
            'starts_at' => now()->addDay()->setTime(14, 0),
            'ends_at' => now()->addDay()->setTime(15, 0),
        ]);

    $cancellationUrl = 'https://booking-platform.test/bookings/1/cancel';

    $this->mock(
        GenerateBookingCancellationUrl::class,
        function ($mock) use ($booking, $customer, $cancellationUrl) {
            $mock->shouldReceive('generate')
                ->once()
                ->with($booking, $customer)
                ->andReturn($cancellationUrl);
        }
    );

    $notification = new BookingCancellationRequestedNotification($booking);

    $mail = $notification->toMail($customer);

    expect($notification->via($customer))
        ->toBe(['mail']);

    expect($mail)
        ->toBeInstanceOf(MailMessage::class);

    expect($mail->subject)
        ->toBe('Manage your booking');

    expect($mail->greeting)
        ->toBe('Hello John Doe');

    expect($mail->introLines)
        ->toContain('You can manage your booking using the link below.')
        ->toContain('Service: Haircut')
        ->toContain('Date: ' . $booking->starts_at->format('F j, Y'))
        ->toContain(
            'Time: '
                . $booking->starts_at->format('g:i A')
                . ' - '
                . $booking->ends_at->format('g:i A')
        );

    expect($mail->actionText)
        ->toBe('Cancel booking');

    expect($mail->actionUrl)
        ->toBe($cancellationUrl);
});
