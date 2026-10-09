<?php

namespace App\Actions\Bookings;

use App\Exceptions\Bookings\BookingException;
use App\Models\Booking;
use App\Models\Customer;

class CancelPublicBooking
{
    public function __construct(
        private CancelBooking $cancelBooking,
    ) {}

    public function cancel(
        Customer $customer,
        int $bookingId,
    ): void {
        $booking = $customer->bookings()
            ->find($bookingId);

        if (! $booking) {
            throw new BookingException(
                'The booking does not belong to this customer.'
            );
        }

        $this->cancelBooking->cancel(
            user: $booking->user,
            bookingId: $booking->id,
        );
    }
}
