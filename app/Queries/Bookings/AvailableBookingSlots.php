<?php

namespace App\Queries\Bookings;

use App\Enums\BookingStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailableBookingSlots
{
    public function get(
        User $user,
        int $serviceId,
        Carbon $date,
        ?int $exceptBookingId = null,
    ): Collection {
        $service = $user->services()
            ->whereKey($serviceId)
            ->where('is_active', true)
            ->firstOrFail();

        $businessHour = $user->businessHours()
            ->where('day_of_week', $date->dayOfWeek)
            ->first();

        if (
            ! $businessHour
            || $businessHour->is_closed
            || ! $businessHour->opens_at
            || ! $businessHour->closes_at
        ) {
            return collect();
        }

        $opening = $date->copy()
            ->setTimeFromTimeString($businessHour->opens_at);

        $closing = $date->copy()
            ->setTimeFromTimeString($businessHour->closes_at);

        $bookings = $user->bookings()
            ->whereIn('status', BookingStatus::occupying())
            ->where('starts_at', '<', $closing)
            ->where('ends_at', '>', $opening)
            ->when(
                $exceptBookingId !== null,
                fn($query) => $query->whereKeyNot($exceptBookingId),
            )
            ->get();

        $slots = collect();

        for (
            $slotStart = $opening->copy();
            $slotStart->copy()->addMinutes($service->duration)->lessThanOrEqualTo($closing);
            $slotStart->addMinutes($service->duration)
        ) {
            $slotEnd = $slotStart->copy()
                ->addMinutes($service->duration);

            if ($slotStart->isPast()) {
                continue;
            }

            $hasOverlap = $bookings->contains(
                fn($booking) =>
                $booking->starts_at->lessThan($slotEnd)
                    && $booking->ends_at->greaterThan($slotStart)
            );

            if ($hasOverlap) {
                continue;
            }

            $slots->push([
                'starts_at' => $slotStart->copy(),
                'ends_at' => $slotEnd,
            ]);
        }

        return $slots;
    }
}
