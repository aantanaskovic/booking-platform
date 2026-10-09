<?php

use App\Actions\Customers\UpdateCustomer;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates a customer for the given user', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+381 60 123 456',
        'notes' => 'Old notes.',
    ]);

    $updatedCustomer = app(UpdateCustomer::class)->update(
        user: $user,
        customerId: $customer->id,
        data: [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+381 60 987 654',
            'notes' => 'Updated notes.',
        ],
    );

    expect($updatedCustomer)
        ->toBeInstanceOf(Customer::class)
        ->and($updatedCustomer->name)->toBe('Jane Doe')
        ->and($updatedCustomer->email)->toBe('jane@example.com')
        ->and($updatedCustomer->phone)->toBe('+381 60 987 654')
        ->and($updatedCustomer->notes)->toBe('Updated notes.');

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'user_id' => $user->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '+381 60 987 654',
        'notes' => 'Updated notes.',
    ]);
});

it('cannot update a customer belonging to another user', function () {
    $user = User::factory()->create();

    $otherUser = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Original Name',
    ]);

    app(UpdateCustomer::class)->update(
        user: $user,
        customerId: $customer->id,
        data: [
            'name' => 'Hacked Name',
        ],
    );
})->throws(ModelNotFoundException::class);
