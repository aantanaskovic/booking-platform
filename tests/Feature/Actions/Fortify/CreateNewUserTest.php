<?php

use App\Actions\Fortify\CreateNewUser;
use GuzzleHttp\Promise\Create;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('generates a slug from the user name', function () {
    $user = app(CreateNewUser::class)->create([
        'name' => 'Demo Business',
        'email' => 'demo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($user->slug)->toBe('demo-business');
});

it('generates a unique slug when the slug already exists', function () {
    $action = app(CreateNewUser::class);

    $action->create([
        'name' => 'Demo Business',
        'email' => 'first@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = $action->create([
        'name' => 'Demo Business',
        'email' => 'second@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($user->slug)->toBe('demo-business-2');
});

it('generates a slug from names with special characters and Serbian letters', function () {
    $user = app(CreateNewUser::class)->create([
        'name' => 'Čarobni Frizerski Salon!',
        'email' => 'salon@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($user->slug)->toBe('carobni-frizerski-salon');
});

it('hashes the user password', function () {
    $user = app(CreateNewUser::class)->create([
        'name' => 'Demo Business',
        'email' => 'demo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($user->password)
        ->not->toBe('password')
        ->and(Hash::check('password', $user->password))
        ->toBeTrue();
});
