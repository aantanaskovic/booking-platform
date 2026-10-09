<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Exceptions\Bookings\BookingSlotUnavailableException;
use App\Models\Booking;
use App\Models\Customer;
use App\Queries\Bookings\AvailableBookingSlots;
use Carbon\Carbon;

class ReschedulePublicBooking
{
    public function __construct(
        private UpdateBooking $updateBooking,
        private AvailableBookingSlots $availableBookingSlots,
    ) {}

    public function reschedule(
        Customer $customer,
        int $bookingId,
        Carbon $startsAt,
        Carbon $endsAt,
    ): Booking {
        $booking = $customer->bookings()
            ->find($bookingId);

        if (! $booking) {
            throw new BookingException(
                'The booking does not belong to this customer.'
            );
        }

        if (! in_array(
            $booking->status,
            [BookingStatus::PENDING, BookingStatus::CONFIRMED],
            true,
        )) {
            throw new BookingException(
                'Only pending or confirmed bookings can be edited.'
            );
        }

        if (! $booking->service->is_active) {
            throw new BookingException(
                'The selected service is currently inactive.'
            );
        }

        $availableSlots = $this->availableBookingSlots->get(
            user: $booking->user,
            serviceId: $booking->service_id,
            date: $startsAt->copy()->startOfDay(),
            exceptBookingId: $booking->id,
        );

        $slotIsAvailable = $availableSlots->contains(
            fn($slot) =>
            $slot['starts_at']->equalTo($startsAt)
                && $slot['ends_at']->equalTo($endsAt)
        );

        if (! $slotIsAvailable) {
            throw new BookingSlotUnavailableException();
        }

        return $this->updateBooking->update(
            user: $booking->user,
            bookingId: $booking->id,
            customerId: $customer->id,
            serviceId: $booking->service_id,
            data: [
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'notes' => $booking->notes,
            ],
        );
    }
}
