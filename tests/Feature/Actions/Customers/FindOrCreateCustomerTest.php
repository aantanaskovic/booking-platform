<?php

use App\Actions\Customers\FindOrCreateCustomer;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a customer when one does not exist', function () {
    $business = User::factory()->create();

    $action = app(FindOrCreateCustomer::class);

    $customer = $action->findOrCreate(
        user: $business,
        data: [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+381 60 123 456',
            'notes' => 'First appointment.',
        ],
    );

    expect($customer->user_id)->toBe($business->id);

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'user_id' => $business->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+381 60 123 456',
        'notes' => 'First appointment.',
    ]);
});

it('returns an existing customer with the same email', function () {
    $business = User::factory()->create();

    $existingCustomer = $business->customers()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+381 60 123 456',
        'notes' => null,
    ]);

    $action = app(FindOrCreateCustomer::class);

    $customer = $action->findOrCreate(
        user: $business,
        data: [
            'name' => 'John Updated',
            'email' => 'john@example.com',
            'phone' => '+381 60 999 999',
            'notes' => 'New booking.',
        ],
    );

    expect($customer->id)->toBe($existingCustomer->id);

    expect($business->customers()->count())->toBe(1);
});

it('creates a separate customer when the same email belongs to another user', function () {
    $firstUser = User::factory()->create();

    $existingCustomer = Customer::factory()->create([
        'user_id' => $firstUser->id,
        'email' => 'john@example.com',
    ]);

    $secondUser = User::factory()->create();

    $customer = app(FindOrCreateCustomer::class)->findOrCreate(
        user: $secondUser,
        data: [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+381 60 123 456',
        ],
    );

    expect($customer->id)
        ->not->toBe($existingCustomer->id)
        ->and($customer->user_id)
        ->toBe($secondUser->id)
        ->and(Customer::count())
        ->toBe(2);
});
