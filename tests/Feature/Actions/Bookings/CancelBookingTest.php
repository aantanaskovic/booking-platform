<?php

use App\Actions\Bookings\CancelBooking;
use App\Enums\BookingStatus;
use App\Exceptions\Bookings\BookingException;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

uses(RefreshDatabase::class);

it('cancels a cancellable booking', function (BookingStatus $status) {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => $status,
    ]);

    $cancelledBooking = app(CancelBooking::class)->cancel(
        user: $user,
        bookingId: $booking->id,
    );

    expect($cancelledBooking->status)
        ->toBe(BookingStatus::CANCELLED);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => BookingStatus::CANCELLED->value,
    ]);
})->with([
    'pending' => BookingStatus::PENDING,
    'confirmed' => BookingStatus::CONFIRMED,
]);

it('cannot cancel a non-cancellable booking', function (BookingStatus $status) {
    $user = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $user->id,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => $status,
    ]);

    expect(fn() => app(CancelBooking::class)->cancel(
        user: $user,
        bookingId: $booking->id,
    ))->toThrow(
        BookingException::class,
        'Only pending or confirmed bookings can be cancelled.'
    );
})->with([
    'cancelled' => BookingStatus::CANCELLED,
    'completed' => BookingStatus::COMPLETED,
]);

it('cannot cancel a booking belonging to another user', function () {
    $user = User::factory()->create();

    $otherUser = User::factory()->create();

    $customer = Customer::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $service = Service::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $otherUser->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => BookingStatus::PENDING,
    ]);

    expect(fn() => app(CancelBooking::class)->cancel(
        user: $user,
        bookingId: $booking->id,
    ))->toThrow(ModelNotFoundException::class);
});

it('does not send a cancellation notification when the transaction rolls back', function () {
    Notification::fake();

    $user = User::factory()->create();
    $customer = Customer::factory()->for($user)->create();
    $service = Service::factory()->for($user)->create();

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'status' => BookingStatus::PENDING,
        ]);

    try {
        DB::transaction(function () use ($user, $booking) {
            app(CancelBooking::class)->cancel(
                user: $user,
                bookingId: $booking->id,
            );

            throw new RuntimeException('Force rollback');
        });
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Force rollback');
    }

    expect($booking->fresh()->status)->toBe(BookingStatus::PENDING);

    Notification::assertNothingSent();
});

it('sends the cancellation notification only after the transaction commits', function () {
    Notification::fake();

    $user = User::factory()->create();
    $customer = Customer::factory()->for($user)->create();
    $service = Service::factory()->for($user)->create();

    $booking = Booking::factory()
        ->for($user)
        ->for($customer)
        ->for($service)
        ->create([
            'status' => BookingStatus::PENDING,
        ]);

    DB::beginTransaction();

    try {
        app(CancelBooking::class)->cancel(
            user: $user,
            bookingId: $booking->id,
        );

        expect($booking->fresh()->status)
            ->toBe(BookingStatus::CANCELLED);

        Notification::assertNothingSent();

        DB::commit();
    } catch (Throwable $exception) {
        DB::rollBack();

        throw $exception;
    }

    Notification::assertSentTo(
        $customer,
        BookingCancelledNotification::class,
    );
});
