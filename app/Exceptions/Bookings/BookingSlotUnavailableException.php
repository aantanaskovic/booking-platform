<?php

namespace App\Exceptions\Bookings;

class BookingSlotUnavailableException extends BookingException
{
    public function __construct()
    {
        parent::__construct(
            'This time slot is no longer available. Please choose another time.'
        );
    }
}
