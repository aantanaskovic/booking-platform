<?php

use App\Actions\BusinessHours\UpdateBusinessHours;
use App\Models\BusinessHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

it('creates business hours for all provided days', function () {
    $user = User::factory()->create();

    $data = [
        0 => [
            'is_closed' => true,
            'opens_at' => null,
            'closes_at' => null,
        ],
        1 => [
            'is_closed' => false,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
        ],
        2 => [
            'is_closed' => false,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
        ],
        3 => [
            'is_closed' => false,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
        ],
        4 => [
            'is_closed' => false,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
        ],
        5 => [
            'is_closed' => false,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
        ],
        6 => [
            'is_closed' => true,
            'opens_at' => null,
            'closes_at' => null,
        ],
    ];

    app(UpdateBusinessHours::class)->update($user, $data);

    expect(
        BusinessHour::query()
            ->where('user_id', $user->id)
            ->count()
    )->toBe(7);

    $monday = BusinessHour::query()
        ->where('user_id', $user->id)
        ->where('day_of_week', 1)
        ->first();

    expect($monday->opens_at)->toBe('09:00')
        ->and($monday->closes_at)->toBe('17:00')
        ->and($monday->is_closed)->toBeFalse();
});

it('clears opening and closing times for a closed day', function () {
    $user = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 6,
        'opens_at' => '09:00:00',
        'closes_at' => '17:00:00',
        'is_closed' => false,
    ]);

    app(UpdateBusinessHours::class)->update($user, [
        6 => [
            'is_closed' => true,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
        ],
    ]);

    $businessHour = $user->businessHours()
        ->where('day_of_week', 6)
        ->first();

    expect($businessHour->is_closed)->toBeTrue()
        ->and($businessHour->opens_at)->toBeNull()
        ->and($businessHour->closes_at)->toBeNull();
});

it('rejects an open day without both opening and closing times', function (array $hours) {
    $user = User::factory()->create();

    expect(fn() => app(UpdateBusinessHours::class)->update($user, [
        1 => $hours,
    ]))
        ->toThrow(InvalidArgumentException::class);
})->with([
    [
        [
            'is_closed' => false,
            'opens_at' => null,
            'closes_at' => '17:00',
        ],
    ],
    [
        [
            'is_closed' => false,
            'opens_at' => '09:00',
            'closes_at' => null,
        ],
    ],
    [
        [
            'is_closed' => false,
            'opens_at' => null,
            'closes_at' => null,
        ],
    ],
]);

it('rejects invalid opening and closing time order', function (string $opensAt, string $closesAt) {
    $user = User::factory()->create();

    expect(fn() => app(UpdateBusinessHours::class)->update($user, [
        1 => [
            'is_closed' => false,
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
        ],
    ]))
        ->toThrow(InvalidArgumentException::class);
})->with([
    ['17:00', '09:00'],
    ['09:00', '09:00'],
]);

it('updates existing business hours', function () {
    $user = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 1,
        'opens_at' => '09:00:00',
        'closes_at' => '17:00:00',
        'is_closed' => false,
    ]);

    app(UpdateBusinessHours::class)->update($user, [
        1 => [
            'is_closed' => false,
            'opens_at' => '10:00',
            'closes_at' => '18:00',
        ],
    ]);

    expect(
        $user->businessHours()
            ->where('day_of_week', 1)
            ->count()
    )->toBe(1);

    $businessHour = $user->businessHours()
        ->where('day_of_week', 1)
        ->first();

    expect($businessHour->opens_at)->toBe('10:00')
        ->and($businessHour->closes_at)->toBe('18:00');
});

it('rolls back all business hours when one day is invalid', function () {
    $user = User::factory()->create();

    expect(fn() => app(UpdateBusinessHours::class)->update($user, [
        1 => [
            'is_closed' => false,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
        ],
        2 => [
            'is_closed' => false,
            'opens_at' => '17:00',
            'closes_at' => '09:00',
        ],
    ]))
        ->toThrow(InvalidArgumentException::class);

    expect(
        BusinessHour::query()
            ->where('user_id', $user->id)
            ->count()
    )->toBe(0);
});
