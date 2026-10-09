<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays service data in the edit form', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'Consultation',
        'description' => 'One hour consultation.',
        'duration' => 60,
        'price' => 50,
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test('pages::services.edit', [
            'serviceId' => $service->id,
        ])
        ->assertSet('name', 'Consultation')
        ->assertSet('description', 'One hour consultation.')
        ->assertSet('duration', '60')
        ->assertSet('price', '50.00')
        ->assertSet('isActive', true);
});

it('requires a service name', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::services.edit', [
            'serviceId' => $service->id,
        ])
        ->set('name', '')
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
        ]);
});

it('updates a service and redirects to the services index', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Service',
        'description' => 'Old description.',
        'duration' => 30,
        'price' => 30,
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test('pages::services.edit', [
            'serviceId' => $service->id,
        ])
        ->set('name', 'Updated Service')
        ->set('description', 'Updated description.')
        ->set('duration', '60')
        ->set('price', '50.00')
        ->set('isActive', false)
        ->call('save')
        ->assertRedirect(route('services.index'));

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'user_id' => $user->id,
        'name' => 'Updated Service',
        'description' => 'Updated description.',
        'duration' => 60,
        'price' => '50.00',
        'is_active' => false,
    ]);
});

it('cannot edit a service belonging to another user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $otherUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $service = Service::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Service',
    ]);

    $this->actingAs($user)
        ->get(route('services.edit', [
            'serviceId' => $service->id,
        ]))
        ->assertNotFound();
});

it('validates the service duration and price', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'duration' => 60,
        'price' => 50,
    ]);

    Livewire::actingAs($user)
        ->test('pages::services.edit', [
            'serviceId' => $service->id,
        ])
        ->set('duration', '0')
        ->set('price', '-10')
        ->call('save')
        ->assertHasErrors([
            'duration' => 'min',
            'price' => 'min',
        ]);
});
