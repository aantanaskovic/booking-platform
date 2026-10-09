<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::guest')] class extends Component {};
?>

<div>
    @auth
        @unless (auth()->user()->hasVerifiedEmail())
            <div class="mx-auto max-w-7xl px-4 pt-6 pb-6 sm:px-6 sm:pb-8 lg:px-8">
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="font-semibold text-amber-900">
                        Please verify your email address.
                    </p>

                    <p class="mt-1 text-sm text-amber-800">
                        We've sent you a verification link. Check your inbox to unlock
                        all features.
                    </p>

                    <a href="{{ route('verification.notice') }}"
                        class="mt-3 inline-block text-sm font-semibold text-amber-900 underline">
                        Resend verification email
                    </a>
                </div>
            </div>
        @endunless
    @endauth

    <section class="relative isolate overflow-hidden">
        <div class="absolute inset-x-0 top-0 -z-10 h-[600px] bg-gradient-to-b from-indigo-50 via-white to-white"></div>

        <div
            class="mx-auto grid max-w-7xl items-center gap-14 px-4 py-20 sm:px-6 sm:py-28 lg:grid-cols-2 lg:px-8 lg:py-32">
            <div>
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-white px-3 py-1.5 text-sm font-medium text-indigo-700 shadow-sm">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    A simpler way to manage appointments
                </div>

                <h1 class="mt-7 max-w-2xl text-4xl font-bold tracking-tight text-gray-950 sm:text-5xl lg:text-6xl">
                    Spend less time managing bookings.
                    <span class="text-indigo-600">More time growing your business.</span>
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-8 text-gray-600">
                    Keep your appointments, customers, services and business hours
                    organized in one place. A clear, simple workspace for your
                    day-to-day booking management.
                </p>

                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                        Create your account
                        <span class="ml-2" aria-hidden="true">&rarr;</span>
                    </a>

                    <a href="{{ route('login') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-6 py-3.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                        I already have an account
                    </a>
                </div>

                <p class="mt-4 text-sm text-gray-500">
                    Set up your workspace and start organizing your appointments.
                </p>
            </div>

            <div class="relative">
                <div class="absolute -inset-4 -z-10 rounded-[2rem] bg-indigo-100/70 blur-2xl"></div>

                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl shadow-indigo-950/10">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Your workspace</p>
                            <p class="mt-1 text-xs text-gray-500">Appointment overview</p>
                        </div>

                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            Organized
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 p-5">
                        <div class="rounded-xl bg-indigo-50 p-4">
                            <p class="text-sm text-indigo-700">Bookings</p>
                            <p class="mt-2 text-3xl font-bold text-indigo-950">24</p>
                            <p class="mt-1 text-xs text-indigo-700">Across your schedule</p>
                        </div>

                        <div class="rounded-xl bg-emerald-50 p-4">
                            <p class="text-sm text-emerald-700">Customers</p>
                            <p class="mt-2 text-3xl font-bold text-emerald-950">18</p>
                            <p class="mt-1 text-xs text-emerald-700">In one place</p>
                        </div>
                    </div>

                    <div class="px-5 pb-5">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-gray-900">Upcoming appointments</h2>
                            <span class="text-xs text-gray-500">Today</span>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center gap-3 rounded-xl border border-gray-100 p-3">
                                <div class="w-16 shrink-0 text-sm font-semibold text-gray-700">09:00</div>
                                <div class="h-10 w-1 rounded-full bg-indigo-500"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-gray-900">Initial consultation</p>
                                    <p class="mt-1 text-xs text-gray-500">Customer appointment</p>
                                </div>
                                <span
                                    class="rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">Pending</span>
                            </div>

                            <div class="flex items-center gap-3 rounded-xl border border-gray-100 p-3">
                                <div class="w-16 shrink-0 text-sm font-semibold text-gray-700">11:00</div>
                                <div class="h-10 w-1 rounded-full bg-emerald-500"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-gray-900">Standard session</p>
                                    <p class="mt-1 text-xs text-gray-500">Customer appointment</p>
                                </div>
                                <span
                                    class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">Confirmed</span>
                            </div>

                            <div class="flex items-center gap-3 rounded-xl border border-gray-100 p-3">
                                <div class="w-16 shrink-0 text-sm font-semibold text-gray-700">14:00</div>
                                <div class="h-10 w-1 rounded-full bg-violet-500"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-gray-900">Extended session</p>
                                    <p class="mt-1 text-xs text-gray-500">Customer appointment</p>
                                </div>
                                <span
                                    class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">Confirmed</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 bg-gray-50 px-5 py-3 text-xs text-gray-500">
                        Example preview — illustrative data, not live account information.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="border-t border-gray-100 bg-white py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Everything in one place</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-gray-950 sm:text-4xl">
                    The essentials for running your schedule
                </h2>
                <p class="mt-4 text-lg leading-8 text-gray-600">
                    Spend less time switching between tools and more time focusing on your customers.
                </p>
            </div>

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <article
                    class="rounded-2xl border border-gray-200 p-6 transition hover:border-indigo-200 hover:shadow-lg hover:shadow-indigo-950/5">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-lg font-bold text-indigo-600">
                        01</div>
                    <h3 class="mt-5 text-lg font-semibold text-gray-900">Bookings</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600">Create, review, reschedule and manage appointments
                        from
                        one workspace.</p>
                </article>

                <article
                    class="rounded-2xl border border-gray-200 p-6 transition hover:border-indigo-200 hover:shadow-lg hover:shadow-indigo-950/5">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-lg font-bold text-indigo-600">
                        02</div>
                    <h3 class="mt-5 text-lg font-semibold text-gray-900">Customers</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600">Keep customer contact details and notes organized
                        and
                        easy to find.</p>
                </article>

                <article
                    class="rounded-2xl border border-gray-200 p-6 transition hover:border-indigo-200 hover:shadow-lg hover:shadow-indigo-950/5">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-lg font-bold text-indigo-600">
                        03</div>
                    <h3 class="mt-5 text-lg font-semibold text-gray-900">Services</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600">Define your services, appointment durations, prices
                        and
                        availability.</p>
                </article>

                <article
                    class="rounded-2xl border border-gray-200 p-6 transition hover:border-indigo-200 hover:shadow-lg hover:shadow-indigo-950/5">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl border border-indigo-100 bg-indigo-50 text-lg font-bold text-indigo-600">
                        04</div>
                    <h3 class="mt-5 text-lg font-semibold text-gray-900">Business hours</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600">Set your working hours so appointment availability
                        follows your schedule.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="border-t border-gray-100 bg-gray-50 py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Get started</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-gray-950 sm:text-4xl">
                    From setup to scheduled
                </h2>
            </div>

            <div class="mt-12 grid gap-8 md:grid-cols-3">
                <div class="relative rounded-2xl bg-white p-7 ring-1 ring-gray-200">
                    <span class="text-sm font-bold text-indigo-600">STEP 01</span>
                    <h3 class="mt-3 text-xl font-semibold text-gray-900">Create your account</h3>
                    <p class="mt-3 text-sm leading-6 text-gray-600">Register and set up your workspace.</p>
                </div>

                <div class="relative rounded-2xl bg-white p-7 ring-1 ring-gray-200">
                    <span class="text-sm font-bold text-indigo-600">STEP 02</span>
                    <h3 class="mt-3 text-xl font-semibold text-gray-900">Configure your business</h3>
                    <p class="mt-3 text-sm leading-6 text-gray-600">Add your services, working hours and customers.</p>
                </div>

                <div class="relative rounded-2xl bg-white p-7 ring-1 ring-gray-200">
                    <span class="text-sm font-bold text-indigo-600">STEP 03</span>
                    <h3 class="mt-3 text-xl font-semibold text-gray-900">Manage appointments</h3>
                    <p class="mt-3 text-sm leading-6 text-gray-600">Keep track of bookings and their status in one
                        place.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-indigo-700 py-16 sm:py-20">
        <div
            class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-8 px-4 sm:px-6 md:flex-row md:items-center lg:px-8">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    Ready to get your schedule organized?
                </h2>
                <p class="mt-4 text-base leading-7 text-indigo-100">
                    Create an account and bring your bookings, customers and services together.
                </p>
            </div>

            <a href="{{ route('register') }}"
                class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white px-6 py-3.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50">
                Get started
                <span class="ml-2" aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </section>
</div>
