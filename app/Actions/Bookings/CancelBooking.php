<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CancelBooking
{
    public function cancel(
        User $user,
        int $bookingId,
    ): Booking {
        return DB::transaction(function () use ($user, $bookingId) {
            $booking = $user->bookings()
                ->lockForUpdate()
                ->findOrFail($bookingId);

            if (! in_array(
                $booking->status,
                [
                    BookingStatus::PENDING,
                    BookingStatus::CONFIRMED,
                ],
                true
            )) {
                throw new BookingException(
                    'Only pending or confirmed bookings can be cancelled.'
                );
            }

            $booking->status = BookingStatus::CANCELLED;
            $booking->save();

            $booking->load('customer');

            DB::afterCommit(function () use ($booking) {
                Notification::send(
                    $booking->customer,
                    new BookingCancelledNotification($booking),
                );
            });

            return $booking;
        });
    }
}
