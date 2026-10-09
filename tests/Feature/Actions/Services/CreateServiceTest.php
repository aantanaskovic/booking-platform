<?php

use App\Models\Service;
use App\Models\User;
use App\Actions\Services\CreateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a service for the given user', function () {
    $user = User::factory()->create();

    $service = app(CreateService::class)->create(
        user: $user,
        data: [
            'name' => 'Consultation',
            'description' => 'One hour consultation.',
            'duration' => 60,
            'price' => 50,
        ],
    );

    expect($service)
        ->toBeInstanceOf(Service::class)
        ->user_id->toBe($user->id)
        ->name->toBe('Consultation')
        ->description->toBe('One hour consultation.')
        ->duration->toBe(60)
        ->price->toBe('50.00')
        ->is_active->toBeTrue();

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'user_id' => $user->id,
        'name' => 'Consultation',
        'duration' => 60,
        'price' => '50.00',
        'is_active' => true,
    ]);
});

it('creates an active service when is_active is not provided', function () {
    $user = User::factory()->create();

    $service = app(CreateService::class)->create(
        user: $user,
        data: [
            'name' => 'Massage',
            'duration' => 45,
            'price' => 30,
        ],
    );

    expect($service->is_active)->toBeTrue();
});
