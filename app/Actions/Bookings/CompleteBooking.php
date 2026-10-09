<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Models\Booking;
use App\Models\User;

class CompleteBooking
{
    public function complete(
        User $user,
        int $bookingId,
    ): Booking {
        $booking = $user->bookings()->findOrFail($bookingId);

        if ($booking->status !== BookingStatus::CONFIRMED) {
            throw new BookingException(
                'Only confirmed bookings can be completed.'
            );
        }

        $booking->status = BookingStatus::COMPLETED;
        $booking->save();

        return $booking;
    }
}
