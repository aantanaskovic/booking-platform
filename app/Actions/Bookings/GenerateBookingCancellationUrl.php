<?php

namespace App\Actions\Bookings;

use App\Exceptions\Bookings\BookingException;
use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Support\Facades\URL;

class GenerateBookingCancellationUrl
{
    public function generate(
        Booking $booking,
        Customer $customer,
    ): string {
        if ($booking->customer_id !== $customer->id) {
            throw new BookingException(
                'The booking does not belong to this customer.'
            );
        }

        return URL::temporarySignedRoute(
            'bookings.cancel',
            now()->addHours(24),
            [
                'bookingId' => $booking->id,
                'customerId' => $customer->id,
            ],
        );
    }
}
