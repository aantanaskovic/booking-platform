<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');
    Route::livewire('/register', 'pages::auth.register')->name('register');

    Route::livewire('/forgot-password', 'pages::auth.forgot-password')->name('password.request');
    Route::livewire('/reset-password/{token}', 'pages::auth.reset-password')->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/email/verify', 'pages::auth.verify-email')->name('verification.notice');
});

Route::middleware(['auth', 'signed'])->group(function () {
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard');
    })->name('verification.verify');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('/bookings', 'pages::bookings.index')->name('bookings.index');
    Route::livewire('/bookings/calendar', 'pages::bookings.calendar')->name('bookings.calendar');

    Route::livewire('/bookings/create', 'pages::bookings.create')->name('bookings.create');
    Route::livewire('/bookings/{bookingId}/edit', 'pages::bookings.edit')->name('bookings.edit');

    Route::livewire('/bookings/{bookingId}', 'pages::bookings.show')->name('bookings.show');

    Route::livewire('/customers', 'pages::customers.index')->name('customers.index');
    Route::livewire('/customers/create', 'pages::customers.create')->name('customers.create');
    Route::livewire('/customers/{customerId}/edit', 'pages::customers.edit')->name('customers.edit');

    Route::livewire('/services', 'pages::services.index')->name('services.index');
    Route::livewire('/services/create', 'pages::services.create')->name('services.create');
    Route::livewire('/services/{serviceId}/edit', 'pages::services.edit')->name('services.edit');

    Route::livewire('/settings/business-hours', 'pages::settings.business-hours')->name('settings.business-hours');
});

Route::livewire('/book/{business}', 'pages::book.index')->name('book.index');

Route::middleware('signed')->group(function () {
    Route::livewire('/bookings/{bookingId}/cancel', 'pages::bookings.cancel')->name('bookings.cancel');
    Route::livewire('/bookings/{bookingId}/manage', 'pages::bookings.manage')->name('bookings.manage');
});
