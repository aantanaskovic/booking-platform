<?php

namespace App\Queries\Bookings;

use App\Enums\BookingDateFilter;
use App\Enums\BookingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class BookingsQuery
{
    public function get(
        User $user,
        ?BookingStatus $status = null,
        ?BookingDateFilter $dateFilter = null,
    ): Collection {
        return $user->bookings()
            ->with(['customer', 'service'])
            ->when(
                $status,
                fn($query) => $query->where('status', $status),
            )
            ->when(
                $dateFilter,
                function ($query) use ($dateFilter) {
                    if ($dateFilter === BookingDateFilter::TODAY) {
                        $query->whereDate('starts_at', today());
                    }

                    if ($dateFilter === BookingDateFilter::UPCOMING) {
                        $query->where(
                            'starts_at',
                            '>=',
                            today()->addDay()->startOfDay(),
                        );
                    }

                    if ($dateFilter === BookingDateFilter::PAST) {
                        $query->where(
                            'starts_at',
                            '<',
                            today()->startOfDay(),
                        );
                    }
                },
            )
            ->orderBy('starts_at')
            ->get();
    }
}
