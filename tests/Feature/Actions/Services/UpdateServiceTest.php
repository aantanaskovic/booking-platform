<?php

use App\Actions\Services\UpdateService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates a service for the given user', function () {
    $user = User::factory()->create();

    $service = Service::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Service',
        'description' => 'Old description',
        'duration' => 30,
        'price' => 30,
        'is_active' => true,
    ]);

    $updatedService = app(UpdateService::class)->update(
        user: $user,
        serviceId: $service->id,
        data: [
            'name' => 'Updated Service',
            'description' => 'Updated description',
            'duration' => 60,
            'price' => 50,
            'is_active' => false,
        ],
    );

    expect($updatedService)
        ->id->toBe($service->id)
        ->user_id->toBe($user->id)
        ->name->toBe('Updated Service')
        ->description->toBe('Updated description')
        ->duration->toBe(60)
        ->price->toBe('50.00')
        ->is_active->toBeFalse();

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'user_id' => $user->id,
        'name' => 'Updated Service',
        'description' => 'Updated description',
        'duration' => 60,
        'price' => '50.00',
        'is_active' => false,
    ]);
});

it('cannot update a service belonging to another user', function () {
    $user = User::factory()->create();

    $otherUser = User::factory()->create();

    $service = Service::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Service',
    ]);

    app(UpdateService::class)->update(
        user: $user,
        serviceId: $service->id,
        data: [
            'name' => 'Hacked Service',
            'description' => null,
            'duration' => 60,
            'price' => 100,
            'is_active' => true,
        ],
    );
})->throws(ModelNotFoundException::class);
