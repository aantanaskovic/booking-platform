<?php

namespace App\Queries\Dashboard;

use App\Enums\BookingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardQuery
{
    public function todayBookingsCount(User $user): int
    {
        return $user->bookings()
            ->whereDate('starts_at', today())
            ->count();
    }

    public function todayBookings(User $user): Collection
    {
        return $user->bookings()
            ->with(['customer', 'service'])
            ->whereDate('starts_at', today())
            ->orderBy('starts_at')
            ->get();
    }

    public function pendingBookingsCount(User $user): int
    {
        return $user->bookings()
            ->where('status', BookingStatus::PENDING)
            ->count();
    }

    public function upcomingBookingsCount(User $user): int
    {
        return $user->bookings()
            ->where(
                'starts_at',
                '>=',
                today()->addDay()->startOfDay(),
            )
            ->count();
    }

    public function upcomingBookings(User $user): Collection
    {
        return $user->bookings()
            ->with(['customer', 'service'])
            ->where(
                'starts_at',
                '>=',
                today()->addDay()->startOfDay(),
            )
            ->orderBy('starts_at')
            ->limit(5)
            ->get();
    }

    public function customersCount(User $user): int
    {
        return $user->customers()->count();
    }
}
