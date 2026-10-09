<?php

namespace App\Console\Commands;

use App\Actions\Bookings\SendBookingReminders;
use Illuminate\Console\Command;

class SendBookingRemindersCommand extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Send reminders for upcoming bookings';

    public function handle(SendBookingReminders $sendBookingReminders): int
    {
        $sendBookingReminders->send();

        $this->info('Booking reminders sent successfully.');

        return self::SUCCESS;
    }
}
