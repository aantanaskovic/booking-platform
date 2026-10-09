<?php

use App\Actions\Customers\CreateCustomer;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a customer for the given user', function () {
    $user = User::factory()->create();

    $customer = app(CreateCustomer::class)->create(
        user: $user,
        data: [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+381 60 123 456',
            'notes' => 'Important customer.',
        ],
    );

    expect($customer)
        ->toBeInstanceOf(Customer::class)
        ->and($customer->user_id)->toBe($user->id)
        ->and($customer->name)->toBe('John Doe')
        ->and($customer->email)->toBe('john@example.com')
        ->and($customer->phone)->toBe('+381 60 123 456')
        ->and($customer->notes)->toBe('Important customer.');

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'user_id' => $user->id,
        'name' => 'John Doe',
    ]);
});

it('creates a customer without optional contact information', function () {
    $user = User::factory()->create();

    $customer = app(CreateCustomer::class)->create(
        user: $user,
        data: [
            'name' => 'John Doe',
        ],
    );

    expect($customer->name)
        ->toBe('John Doe')
        ->and($customer->email)->toBeNull()
        ->and($customer->phone)->toBeNull()
        ->and($customer->notes)->toBeNull();
});
