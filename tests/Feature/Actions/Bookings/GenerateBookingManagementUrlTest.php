<?php

use App\Actions\Bookings\GenerateBookingManagementUrl;
use App\Exceptions\Bookings\BookingException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

it('generates a temporary signed management url for a booking', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create();

    $service = Service::factory()
        ->for($user)
        ->create();

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create();

    $url = app(GenerateBookingManagementUrl::class)->generate(
        $booking,
        $customer,
    );

    expect($url)
        ->toContain("/bookings/{$booking->id}/manage")
        ->toContain('customerId=')
        ->toContain('signature=')
        ->toContain('expires=');

    expect(URL::hasValidSignature(
        request()->create($url)
    ))->toBeTrue();
});

it('does not generate a management url for another customer', function () {
    $user = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create();

    $otherCustomer = Customer::factory()
        ->for($user)
        ->create();

    $service = Service::factory()
        ->for($user)
        ->create();

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create();

    expect(fn() => app(GenerateBookingManagementUrl::class)->generate(
        $booking,
        $otherCustomer,
    ))->toThrow(BookingException::class);
});

it('generates a management url that expires after 24 hours', function () {
    $this->travelTo(Carbon::parse('2026-10-01 12:00'));

    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
    ]);

    $url = app(GenerateBookingManagementUrl::class)->generate(
        booking: $booking,
        customer: $customer,
    );

    expect(URL::hasValidSignature(
        Request::create($url)
    ))->toBeTrue();

    $this->travelTo(Carbon::parse('2026-10-02 12:01'));

    expect(URL::hasValidSignature(
        Request::create($url)
    ))->toBeFalse();

    $this->travelBack();
});
