<?php

namespace App\Queries\Bookings;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class BookingsCalendarQuery
{
    public function get(
        User $user,
        Carbon $from,
        Carbon $to,
    ): Collection {
        return $user->bookings()
            ->with(['customer', 'service'])
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from)
            ->orderBy('starts_at')
            ->get();
    }
}
