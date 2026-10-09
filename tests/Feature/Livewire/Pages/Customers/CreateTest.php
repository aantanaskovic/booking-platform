<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('requires a customer name', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::customers.create')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
        ]);
});

it('creates a customer and redirects to the customers index', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::customers.create')
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('phone', '+381 60 123 456')
        ->set('notes', 'Important customer.')
        ->call('save')
        ->assertRedirect(route('customers.index'));

    $this->assertDatabaseHas('customers', [
        'user_id' => $user->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+381 60 123 456',
        'notes' => 'Important customer.',
    ]);
});

it('creates a customer without optional contact information', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::customers.create')
        ->set('name', 'John Doe')
        ->call('save')
        ->assertRedirect(route('customers.index'));

    $this->assertDatabaseHas('customers', [
        'user_id' => $user->id,
        'name' => 'John Doe',
        'email' => null,
        'phone' => null,
        'notes' => null,
    ]);
});

it('validates the customer email', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::customers.create')
        ->set('name', 'John Doe')
        ->set('email', 'not-an-email')
        ->call('save')
        ->assertHasErrors([
            'email' => 'email',
        ]);
});
