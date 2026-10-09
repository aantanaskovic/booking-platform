<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification
{
    public function __construct(
        private Booking $booking,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Booking cancelled')
            ->greeting("Hello {$notifiable->name}")
            ->line('Your booking has been cancelled.')
            ->line("Service: {$this->booking->service->name}")
            ->line(
                'Date: ' . $this->booking->starts_at->format('F j, Y')
            )
            ->line(
                'Time: ' . $this->booking->starts_at->format('g:i A')
                    . ' - '
                    . $this->booking->ends_at->format('g:i A')
            );
    }
}
