<?php

use App\Models\BusinessHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays default business hours when none are saved', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.business-hours')
        ->assertSet('hours', [
            0 => [
                'is_closed' => false,
                'opens_at' => '09:00',
                'closes_at' => '17:00',
            ],
            1 => [
                'is_closed' => false,
                'opens_at' => '09:00',
                'closes_at' => '17:00',
            ],
            2 => [
                'is_closed' => false,
                'opens_at' => '09:00',
                'closes_at' => '17:00',
            ],
            3 => [
                'is_closed' => false,
                'opens_at' => '09:00',
                'closes_at' => '17:00',
            ],
            4 => [
                'is_closed' => false,
                'opens_at' => '09:00',
                'closes_at' => '17:00',
            ],
            5 => [
                'is_closed' => false,
                'opens_at' => '09:00',
                'closes_at' => '17:00',
            ],
            6 => [
                'is_closed' => false,
                'opens_at' => '09:00',
                'closes_at' => '17:00',
            ],
        ]);
});

it('loads the authenticated user business hours', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 1,
        'opens_at' => '10:00',
        'closes_at' => '18:00',
        'is_closed' => false,
    ]);

    BusinessHour::factory()->create([
        'user_id' => $user->id,
        'day_of_week' => 6,
        'opens_at' => null,
        'closes_at' => null,
        'is_closed' => true,
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.business-hours')
        ->assertSet('hours.1', [
            'is_closed' => false,
            'opens_at' => '10:00',
            'closes_at' => '18:00',
        ])
        ->assertSet('hours.6', [
            'is_closed' => true,
            'opens_at' => null,
            'closes_at' => null,
        ]);
});

it('does not load another users business hours', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $otherUser = User::factory()->create();

    BusinessHour::factory()->create([
        'user_id' => $otherUser->id,
        'day_of_week' => 1,
        'opens_at' => '12:00',
        'closes_at' => '20:00',
        'is_closed' => false,
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.business-hours')
        ->assertSet('hours.1', [
            'is_closed' => false,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
        ]);
});

it('updates business hours', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.business-hours')
        ->set('hours.1', [
            'is_closed' => false,
            'opens_at' => '10:00',
            'closes_at' => '18:00',
        ])
        ->set('hours.6', [
            'is_closed' => true,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
        ])
        ->call('save')
        ->assertHasNoErrors();

    $monday = $user->businessHours()
        ->where('day_of_week', 1)
        ->first();

    expect($monday->opens_at)->toBe('10:00')
        ->and($monday->closes_at)->toBe('18:00')
        ->and($monday->is_closed)->toBeFalse();

    $saturday = $user->businessHours()
        ->where('day_of_week', 6)
        ->first();

    expect($saturday->is_closed)->toBeTrue()
        ->and($saturday->opens_at)->toBeNull()
        ->and($saturday->closes_at)->toBeNull();
});

it('validates business hour time format', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.business-hours')
        ->set('hours.1.opens_at', 'invalid')
        ->call('save')
        ->assertHasErrors([
            'hours.1.opens_at' => 'date_format',
        ]);
});

it('validates the required business hour structure', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.business-hours')
        ->set('hours.1.is_closed', false)
        ->set('hours.1.opens_at', null)
        ->call('save')
        ->assertHasErrors([
            'hours',
        ]);
});
