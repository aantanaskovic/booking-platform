<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays the customer data in the edit form', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+381 60 123 456',
        'notes' => 'Important customer.',
    ]);

    Livewire::actingAs($user)
        ->test('pages::customers.edit', [
            'customerId' => $customer->id,
        ])
        ->assertSet('name', 'John Doe')
        ->assertSet('email', 'john@example.com')
        ->assertSet('phone', '+381 60 123 456')
        ->assertSet('notes', 'Important customer.');
});

it('requires a customer name', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Doe',
    ]);

    Livewire::actingAs($user)
        ->test('pages::customers.edit', [
            'customerId' => $customer->id,
        ])
        ->set('name', '')
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
        ]);
});

it('updates a customer and redirects to the customers index', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+381 60 123 456',
        'notes' => 'Old notes.',
    ]);

    Livewire::actingAs($user)
        ->test('pages::customers.edit', [
            'customerId' => $customer->id,
        ])
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('phone', '+381 60 987 654')
        ->set('notes', 'Updated notes.')
        ->call('save')
        ->assertRedirect(route('customers.index'));

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'user_id' => $user->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '+381 60 987 654',
        'notes' => 'Updated notes.',
    ]);
});

it('cannot edit a customer belonging to another user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $otherUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Customer',
    ]);

    $this->actingAs($user)
        ->get(route('customers.edit', [
            'customerId' => $customer->id,
        ]))
        ->assertNotFound();
});

it('validates the customer email', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    Livewire::actingAs($user)
        ->test('pages::customers.edit', [
            'customerId' => $customer->id,
        ])
        ->set('email', 'not-an-email')
        ->call('save')
        ->assertHasErrors([
            'email' => 'email',
        ]);
});
