<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Exceptions\Bookings\BookingSlotUnavailableException;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateBooking
{
    public function update(
        User $user,
        int $bookingId,
        int $customerId,
        int $serviceId,
        array $data,
    ): Booking {
        return DB::transaction(function () use (
            $user,
            $bookingId,
            $customerId,
            $serviceId,
            $data,
        ) {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $booking = $lockedUser->bookings()->findOrFail($bookingId);

            if (
                ! in_array(
                    $booking->status,
                    [
                        BookingStatus::PENDING,
                        BookingStatus::CONFIRMED,
                    ],
                    true
                )
            ) {
                throw new BookingException(
                    'Only pending or confirmed bookings can be edited.'
                );
            }

            $customer = $lockedUser->customers()->findOrFail($customerId);
            $service = $lockedUser->services()->findOrFail($serviceId);

            if (! $service->is_active) {
                throw new BookingException(
                    'The selected service is currently inactive.'
                );
            }

            $startsAt = Carbon::parse($data['starts_at']);
            $endsAt = Carbon::parse($data['ends_at']);

            if ($startsAt->greaterThanOrEqualTo($endsAt)) {
                throw new BookingException(
                    'The booking end time must be after the start time.'
                );
            }

            if (! $startsAt->copy()->addMinutes($service->duration)->equalTo($endsAt)) {
                throw new BookingException(
                    'The booking duration must match the selected service.'
                );
            }

            $hasOverlap = $lockedUser->bookings()
                ->whereKeyNot($booking->id)
                ->whereIn('status', BookingStatus::occupying())
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

            if ($hasOverlap) {
                throw new BookingSlotUnavailableException();
            }

            $booking->customer()->associate($customer);
            $booking->service()->associate($service);

            $booking->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'notes' => $data['notes'] ?? null,
            ]);

            return $booking;
        });
    }
}
