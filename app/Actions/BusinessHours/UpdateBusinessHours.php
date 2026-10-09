<?php

namespace App\Actions\BusinessHours;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdateBusinessHours
{
    public function update(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            foreach ($data as $dayOfWeek => $hours) {
                $isClosed = $hours['is_closed'];

                if ($isClosed) {
                    $user->businessHours()->updateOrCreate(
                        ['day_of_week' => $dayOfWeek],
                        [
                            'opens_at' => null,
                            'closes_at' => null,
                            'is_closed' => true,
                        ],
                    );

                    continue;
                }

                if (
                    empty($hours['opens_at'])
                    || empty($hours['closes_at'])
                ) {
                    throw new InvalidArgumentException(
                        'Open business days must have opening and closing times.'
                    );
                }

                if ($hours['opens_at'] >= $hours['closes_at']) {
                    throw new InvalidArgumentException(
                        'Opening time must be before closing time.'
                    );
                }

                $user->businessHours()->updateOrCreate(
                    ['day_of_week' => $dayOfWeek],
                    [
                        'opens_at' => $hours['opens_at'],
                        'closes_at' => $hours['closes_at'],
                        'is_closed' => false,
                    ],
                );
            }
        });
    }
}
