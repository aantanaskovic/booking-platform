<?php

use App\Actions\Bookings\SendBookingReminders;
use Illuminate\Support\Facades\Artisan;
use Mockery\MockInterface;

it('sends booking reminders through the reminder action', function () {
    $this->mock(SendBookingReminders::class, function (MockInterface $mock) {
        $mock->shouldReceive('send')
            ->once();
    });

    $exitCode = Artisan::call('bookings:send-reminders');

    expect($exitCode)->toBe(0);

    expect(Artisan::output())
        ->toContain('Booking reminders sent successfully.');
});
