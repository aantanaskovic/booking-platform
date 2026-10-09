<?php

use App\Actions\Bookings\GenerateBookingCancellationUrl;
use App\Exceptions\Bookings\BookingException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

it('generates a temporary signed cancellation url for a booking', function () {
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

    $url = app(GenerateBookingCancellationUrl::class)->generate(
        $booking,
        $customer,
    );

    expect($url)
        ->toContain("/bookings/{$booking->id}/cancel")
        ->toContain('signature=')
        ->toContain('expires=');

    expect(URL::hasValidSignature(
        request()->create($url)
    ))->toBeTrue();
});

it('generates a cancellation url that expires after 24 hours', function () {
    $now = Carbon::create(2026, 10, 3, 12);

    Carbon::setTestNow($now);

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

    $url = app(GenerateBookingCancellationUrl::class)->generate(
        $booking,
        $customer,
    );

    $request = request()->create($url);

    expect(URL::hasValidSignature($request))
        ->toBeTrue();

    Carbon::setTestNow($now->copy()->addHours(24)->addSecond());

    expect(URL::hasValidSignature($request))
        ->toBeFalse();
});

it('does not generate a cancellation url for a booking belonging to another customer', function () {
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

    expect(fn() => app(GenerateBookingCancellationUrl::class)->generate(
        $booking,
        $otherCustomer,
    ))->toThrow(BookingException::class);
});

it('does not generate a cancellation url across tenants', function () {
    $user = User::factory()->create();

    $otherUser = User::factory()->create();

    $customer = Customer::factory()
        ->for($user)
        ->create();

    $otherCustomer = Customer::factory()
        ->for($otherUser)
        ->create();

    $service = Service::factory()
        ->for($user)
        ->create();

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create();

    expect(fn() => app(GenerateBookingCancellationUrl::class)->generate(
        $booking,
        $otherCustomer,
    ))->toThrow(BookingException::class);
});
