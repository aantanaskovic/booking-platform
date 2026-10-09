<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingCancellationRequestedNotification;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Support\Facades\Notification;

class ConfirmBooking
{
    public function confirm(
        User $user,
        int $bookingId,
    ): Booking {
        $booking = $user->bookings()->findOrFail($bookingId);

        if ($booking->status !== BookingStatus::PENDING) {
            throw new BookingException(
                'Only pending bookings can be confirmed.'
            );
        }

        $booking->status = BookingStatus::CONFIRMED;
        $booking->save();

        Notification::send(
            $booking->customer,
            new BookingConfirmedNotification($booking),
        );

        Notification::send(
            $booking->customer,
            new BookingCancellationRequestedNotification($booking),
        );

        return $booking;
    }
}
