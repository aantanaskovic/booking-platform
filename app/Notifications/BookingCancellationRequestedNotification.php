<?php

namespace App\Notifications;

use App\Actions\Bookings\GenerateBookingCancellationUrl;
use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancellationRequestedNotification extends Notification
{
    public function __construct(
        private Booking $booking,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(
        object $notifiable,
    ): MailMessage {
        $cancellationUrl = app(
            GenerateBookingCancellationUrl::class
        )->generate(
            $this->booking,
            $notifiable,
        );

        return (new MailMessage)
            ->subject('Manage your booking')
            ->greeting("Hello {$notifiable->name}")
            ->line('You can manage your booking using the link below.')
            ->line("Service: {$this->booking->service->name}")
            ->line(
                'Date: ' . $this->booking->starts_at->format('F j, Y')
            )
            ->line(
                'Time: ' . $this->booking->starts_at->format('g:i A')
                    . ' - '
                    . $this->booking->ends_at->format('g:i A')
            )
            ->action('Cancel booking', $cancellationUrl);
    }
}
