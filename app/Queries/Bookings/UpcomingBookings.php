<?php

namespace App\Queries\Bookings;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UpcomingBookings
{
    public function get(User $user): Collection
    {
        return $user->bookings()
            ->with(['customer', 'service'])
            ->whereIn('status', BookingStatus::occupying())
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->get();
    }
}
