<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('requires a service name', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::services.create')
        ->set('name', '')
        ->set('duration', '')
        ->set('price', '')
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
            'duration' => 'required',
            'price' => 'required',
        ]);
});

it('creates a service and redirects to the services index', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::services.create')
        ->set('name', 'John Doe')
        ->set('description', 'Important Description')
        ->set('duration', '30')
        ->set('price', '50.00')
        ->call('save')
        ->assertRedirect(route('services.index'));

    $this->assertDatabaseHas('services', [
        'user_id' => $user->id,
        'name' => 'John Doe',
        'description' => 'Important Description',
        'duration' => '30',
        'price' => '50.00',
        'is_active' => true,
    ]);
});

it('creates a service without optional information', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::services.create')
        ->set('name', 'John Doe')
        ->set('duration', '30')
        ->set('price', '50.00')
        ->call('save')
        ->assertRedirect(route('services.index'));

    $this->assertDatabaseHas('services', [
        'user_id' => $user->id,
        'name' => 'John Doe',
        'description' => null,
        'duration' => '30',
        'price' => '50.00',
        'is_active' => true,
    ]);
});

it('validates the service duration and price', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::services.create')
        ->set('name', 'Consultation')
        ->set('duration', '0')
        ->set('price', '-10')
        ->call('save')
        ->assertHasErrors([
            'duration' => 'min',
            'price' => 'min',
        ]);
});
