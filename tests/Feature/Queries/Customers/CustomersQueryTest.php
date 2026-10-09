<?php

use App\Models\Customer;
use App\Models\User;
use App\Queries\Customers\CustomersQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns customers for the given user', function () {
    $user = User::factory()->create();

    Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Doe',
    ]);

    Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'Jane Doe',
    ]);

    $otherUser = User::factory()->create();

    Customer::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Customer',
    ]);

    $customers = app(CustomersQuery::class)->get($user);

    expect($customers)
        ->toHaveCount(2)
        ->and($customers->pluck('name')->all())
        ->toBe([
            'Jane Doe',
            'John Doe',
        ]);
});
