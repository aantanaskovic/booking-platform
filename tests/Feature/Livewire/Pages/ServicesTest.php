<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays services for the authenticated user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'Consultation',
    ]);

    Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'Massage',
    ]);

    Livewire::actingAs($user)
        ->test('pages::services.index')
        ->assertSee('Consultation')
        ->assertSee('Massage');
});

it('does not display services belonging to another user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $otherUser = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Service',
    ]);

    Service::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Service',
    ]);

    Livewire::actingAs($user)
        ->test('pages::services.index')
        ->assertSee('My Service')
        ->assertDontSee('Other Service');
});

it('displays empty state when there are no services', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::services.index')
        ->assertSee('No services yet.')
        ->assertSee('Add your first service to get started.');
});
