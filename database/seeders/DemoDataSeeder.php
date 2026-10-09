<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo Studio',
            'slug' => 'demo-studio',
            'email' => 'demo@booking-platform.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $this->seedBusinessHours($user);

        $customers = $this->seedCustomers($user);
        $services = $this->seedServices($user);

        $this->seedBookings($user, $customers, $services);
    }

    private function seedBusinessHours(User $user): void
    {
        foreach (range(0, 6) as $day) {
            $isSunday = $day === 0;
            $isSaturday = $day === 6;

            BusinessHour::factory()->for($user)->create([
                'day_of_week' => $day,
                'opens_at' => $isSunday ? null : ($isSaturday ? '10:00:00' : '09:00:00'),
                'closes_at' => $isSunday ? null : ($isSaturday ? '14:00:00' : '17:00:00'),
                'is_closed' => $isSunday,
            ]);
        }
    }

    private function seedCustomers(User $user): array
    {
        $customers = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '+381 60 111 1001'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'phone' => '+381 60 111 1002'],
            ['name' => 'Mark Wilson', 'email' => 'mark@example.com', 'phone' => '+381 60 111 1003'],
            ['name' => 'Emily Brown', 'email' => 'emily@example.com', 'phone' => '+381 60 111 1004'],
            ['name' => 'Michael Davis', 'email' => 'michael@example.com', 'phone' => '+381 60 111 1005'],
            ['name' => 'Sarah Miller', 'email' => 'sarah@example.com', 'phone' => '+381 60 111 1006'],
            ['name' => 'David Taylor', 'email' => 'david@example.com', 'phone' => '+381 60 111 1007'],
            ['name' => 'Laura Anderson', 'email' => 'laura@example.com', 'phone' => '+381 60 111 1008'],
            ['name' => 'Daniel Thomas', 'email' => 'daniel@example.com', 'phone' => '+381 60 111 1009'],
            ['name' => 'Olivia Martin', 'email' => 'olivia@example.com', 'phone' => '+381 60 111 1010'],
            ['name' => 'James White', 'email' => 'james@example.com', 'phone' => '+381 60 111 1011'],
            ['name' => 'Sophia Harris', 'email' => 'sophia@example.com', 'phone' => '+381 60 111 1012'],
        ];

        return collect($customers)
            ->map(fn(array $data) => Customer::factory()
                ->for($user)
                ->create($data))
            ->all();
    }

    private function seedServices(User $user): array
    {
        $services = [
            [
                'name' => 'Initial Consultation',
                'description' => 'An introductory appointment to discuss requirements and goals.',
                'duration' => 60,
                'price' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Standard Session',
                'description' => 'A regular appointment for existing customers.',
                'duration' => 45,
                'price' => 35,
                'is_active' => true,
            ],
            [
                'name' => 'Extended Session',
                'description' => 'A longer appointment for more complex requirements.',
                'duration' => 90,
                'price' => 75,
                'is_active' => true,
            ],
            [
                'name' => 'Quick Follow-up',
                'description' => 'A short follow-up appointment.',
                'duration' => 30,
                'price' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Legacy Service',
                'description' => 'An inactive service retained for historical bookings.',
                'duration' => 60,
                'price' => 40,
                'is_active' => false,
            ],
        ];

        return collect($services)
            ->map(fn(array $data) => Service::factory()
                ->for($user)
                ->create($data))
            ->all();
    }

    private function seedBookings(
        User $user,
        array $customers,
        array $services,
    ): void {
        $offsets = [-28, -25, -22, -19, -16, -13, -10, -7, -4, -1, 1, 4, 7, 10, 13];
        $times = ['09:00', '11:00', '14:00'];
        $bookingIndex = 0;

        foreach ($offsets as $offset) {
            $date = Carbon::today()->addDays($offset);

            // Keep the sample appointments within the Monday-Friday schedule.
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($times as $timeIndex => $time) {
                // Add a third appointment on selected days for a richer demo.
                if ($timeIndex === 2 && abs($offset) % 3 !== 1) {
                    continue;
                }

                $service = $services[$bookingIndex % count($services)];

                // Inactive services can appear in historical bookings only.
                if (! $service->is_active && $offset >= 0) {
                    $service = $services[0];
                }

                $startsAt = $date->copy()->setTimeFromTimeString($time);
                $endsAt = $startsAt->copy()->addMinutes($service->duration);

                $status = $this->bookingStatus($offset, $bookingIndex);

                Booking::factory()->create([
                    'user_id' => $user->id,
                    'customer_id' => $customers[$bookingIndex % count($customers)]->id,
                    'service_id' => $service->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => $status,
                    'notes' => $bookingIndex % 3 === 0
                        ? 'Demo booking created for portfolio demonstration.'
                        : null,
                    'reminder_sent_at' => null,
                ]);

                $bookingIndex++;
            }
        }
    }

    private function bookingStatus(int $dayOffset, int $index): BookingStatus
    {
        if ($dayOffset < 0) {
            return $index % 4 === 0
                ? BookingStatus::CANCELLED
                : BookingStatus::COMPLETED;
        }

        return match ($index % 4) {
            0 => BookingStatus::PENDING,
            1 => BookingStatus::CONFIRMED,
            2 => BookingStatus::PENDING,
            default => BookingStatus::CANCELLED,
        };
    }
}
