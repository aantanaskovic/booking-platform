<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReminderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendBookingReminders
{
    public function send(): void
    {
        $bookings = Booking::query()
            ->whereIn('status', BookingStatus::occupying())
            ->whereNull('reminder_sent_at')
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', now()->addHours(24))
            ->get();

        foreach ($bookings as $booking) {
            DB::transaction(function () use ($booking) {
                $booking = Booking::query()
                    ->with('customer')
                    ->whereKey($booking->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($booking->reminder_sent_at !== null) {
                    return;
                }

                Notification::send(
                    $booking->customer,
                    new BookingReminderNotification($booking),
                );

                $booking->update([
                    'reminder_sent_at' => now(),
                ]);
            });
        }
    }
}
