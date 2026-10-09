<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Exceptions\Bookings\BookingSlotUnavailableException;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateBooking
{
    public function create(
        User $user,
        int $customerId,
        int $serviceId,
        array $data,
    ): Booking {
        return DB::transaction(function () use (
            $user,
            $customerId,
            $serviceId,
            $data,
        ) {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

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
                ->whereIn('status', BookingStatus::occupying())
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

            if ($hasOverlap) {
                throw new BookingSlotUnavailableException();
            }

            $booking = $lockedUser->bookings()->make([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => BookingStatus::PENDING,
                'notes' => $data['notes'] ?? null,
            ]);

            $booking->customer()->associate($customer);
            $booking->service()->associate($service);

            $booking->save();

            return $booking;
        });
    }
}
