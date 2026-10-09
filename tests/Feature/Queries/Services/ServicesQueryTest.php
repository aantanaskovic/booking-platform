<?php

use App\Models\Service;
use App\Models\User;
use App\Queries\Services\ServicesQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns services for the given user', function () {
    $user = User::factory()->create();

    Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Doe',
    ]);

    Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'Jane Doe',
    ]);

    $otherUser = User::factory()->create();

    Service::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Service',
    ]);

    $services = app(ServicesQuery::class)->get($user);

    expect($services)
        ->toHaveCount(2)
        ->and($services->pluck('name')->all())
        ->toBe([
            'Jane Doe',
            'John Doe',
        ]);
});
