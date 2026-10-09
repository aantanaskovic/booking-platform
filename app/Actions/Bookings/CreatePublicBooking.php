<?php

namespace App\Actions\Bookings;

use App\Actions\Customers\FindOrCreateCustomer;
use App\Exceptions\Bookings\BookingException;
use App\Exceptions\Bookings\BookingSlotUnavailableException;
use App\Models\Booking;
use App\Models\User;
use App\Queries\Bookings\AvailableBookingSlots;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreatePublicBooking
{
    public function __construct(
        private FindOrCreateCustomer $findOrCreateCustomer,
        private CreateBooking $createBooking,
        private AvailableBookingSlots $availableBookingSlots,
    ) {}

    public function create(
        User $user,
        int $serviceId,
        array $customerData,
        array $bookingData,
    ): Booking {
        return DB::transaction(function () use (
            $user,
            $serviceId,
            $customerData,
            $bookingData,
        ) {
            $startsAt = Carbon::parse($bookingData['starts_at']);
            $endsAt = Carbon::parse($bookingData['ends_at']);

            $serviceIsAvailable = $user->services()
                ->whereKey($serviceId)
                ->where('is_active', true)
                ->exists();

            if (! $serviceIsAvailable) {
                throw new BookingException(
                    'The selected service is unavailable. Please choose another service.'
                );
            }

            $availableSlots = $this->availableBookingSlots->get(
                user: $user,
                serviceId: $serviceId,
                date: $startsAt->copy()->startOfDay(),
            );

            $slotIsAvailable = $availableSlots->contains(
                fn($slot) =>
                $slot['starts_at']->equalTo($startsAt)
                    && $slot['ends_at']->equalTo($endsAt)
            );

            if (! $slotIsAvailable) {
                throw new BookingSlotUnavailableException();
            }

            $customer = $this->findOrCreateCustomer->findOrCreate(
                user: $user,
                data: $customerData,
            );

            return $this->createBooking->create(
                user: $user,
                customerId: $customer->id,
                serviceId: $serviceId,
                data: $bookingData,
            );
        });
    }
}
