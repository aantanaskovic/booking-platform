<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays customers for the authenticated user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'John Doe',
    ]);

    Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'Jane Doe',
    ]);

    Livewire::actingAs($user)
        ->test('pages::customers.index')
        ->assertStatus(200)
        ->assertSee('John Doe')
        ->assertSee('Jane Doe');
});

it('does not display customers belonging to another user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $otherUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Customer::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Customer',
    ]);

    Livewire::actingAs($user)
        ->test('pages::customers.index')
        ->assertDontSee('Other Customer');
});

it('displays an empty state when there are no customers', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::customers.index')
        ->assertStatus(200)
        ->assertSee('No customers yet.');
});
