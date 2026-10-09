<?php

use App\Actions\Bookings\CreateBooking;
use App\Actions\Bookings\CreatePublicBooking;
use App\Actions\Customers\FindOrCreateCustomer;
use App\Exceptions\Bookings\BookingException;
use App\Models\User;
use App\Queries\Bookings\AvailableBookingSlots;
use Carbon\Carbon;
use Livewire\Component;

new class extends Component {
    public User $businessUser;

    public $services;

    public ?int $selectedServiceId = null;

    public ?string $selectedDate = null;

    public array $availableSlots = [];

    public ?string $selectedStartAt = null;

    public ?string $selectedEndAt = null;

    public string $customerName = '';

    public string $customerEmail = '';

    public string $customerPhone = '';

    public string $customerNotes = '';

    public bool $bookingConfirmed = false;

    public ?string $confirmationServiceName = null;

    public ?string $confirmationDate = null;

    public ?string $confirmationStartTime = null;

    public ?string $confirmationEndTime = null;

    public function mount(string $business): void
    {
        $this->businessUser = User::query()->where('slug', $business)->firstOrFail();

        $this->services = $this->businessUser->services()->where('is_active', true)->orderBy('name')->get();
    }

    public function selectService(int $serviceId): void
    {
        $service = $this->businessUser->services()->whereKey($serviceId)->where('is_active', true)->first();

        if (!$service) {
            return;
        }

        $this->selectedServiceId = $service->id;
        $this->selectedDate = null;
        $this->availableSlots = [];
        $this->selectedStartAt = null;
        $this->selectedEndAt = null;

        $this->js(
            <<<'JS'
                setTimeout(() => {
                    document.getElementById('booking-date-section')?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    });
                }, 50);
            JS
            ,
        );
    }

    public function selectDate(string $date, AvailableBookingSlots $availableBookingSlots): void
    {
        if (!$this->selectedServiceId) {
            return;
        }

        $this->selectedDate = $date;
        $this->selectedStartAt = null;
        $this->selectedEndAt = null;

        $this->availableSlots = $availableBookingSlots->get(user: $this->businessUser, serviceId: $this->selectedServiceId, date: Carbon::parse($date))->all();
    }

    public function selectSlot(string $startAt): void
    {
        $this->selectedStartAt = null;
        $this->selectedEndAt = null;

        foreach ($this->availableSlots as $slot) {
            if ($slot['starts_at']->format('H:i') !== $startAt) {
                continue;
            }

            $this->selectedStartAt = $slot['starts_at']->format('Y-m-d H:i:s');
            $this->selectedEndAt = $slot['ends_at']->format('Y-m-d H:i:s');

            return;
        }
    }

    protected function rules(): array
    {
        return [
            'customerName' => ['required', 'string', 'max:255'],
            'customerEmail' => ['required', 'email', 'max:255'],
            'customerPhone' => ['required', 'string', 'max:50'],
            'customerNotes' => ['nullable', 'string'],
            'selectedServiceId' => ['required', 'integer'],
            'selectedStartAt' => ['required', 'date'],
            'selectedEndAt' => ['required', 'date', 'after:selectedStartAt'],
        ];
    }

    public function book(CreatePublicBooking $createPublicBooking): void
    {
        $this->validate();

        try {
            $createPublicBooking->create(
                user: $this->businessUser,
                serviceId: $this->selectedServiceId,
                customerData: [
                    'name' => $this->customerName,
                    'email' => $this->customerEmail,
                    'phone' => $this->customerPhone,
                    'notes' => $this->customerNotes ?: null,
                ],
                bookingData: [
                    'starts_at' => $this->selectedStartAt,
                    'ends_at' => $this->selectedEndAt,
                    'notes' => $this->customerNotes ?: null,
                ],
            );

            $this->confirmationServiceName = $this->services->firstWhere('id', $this->selectedServiceId)->name;

            $this->confirmationDate = Carbon::parse($this->selectedStartAt)->format('M j, Y');

            $this->confirmationStartTime = Carbon::parse($this->selectedStartAt)->format('H:i');

            $this->confirmationEndTime = Carbon::parse($this->selectedEndAt)->format('H:i');

            $this->bookingConfirmed = true;

            $this->bookingConfirmed = true;
        } catch (BookingSlotUnavailableException) {
            $this->addError('booking', 'This time slot is no longer available. Please choose another time.');

            return;
        } catch (BookingException $exception) {
            $this->addError('booking', $exception->getMessage());

            return;
        }
    }
};
?>

<div class="min-h-screen bg-gray-50">
    @if ($bookingConfirmed)
        <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                    <span class="text-xl text-green-600">
                        ✓
                    </span>
                </div>

                <h1 class="mt-5 text-2xl font-semibold text-gray-900">
                    Appointment booked
                </h1>

                <p class="mt-2 text-sm text-gray-600">
                    Your appointment has been successfully booked.
                </p>

                <div class="mt-8 rounded-lg bg-gray-50 p-5 text-left">
                    <dl class="space-y-4">
                        <div class="flex justify-between gap-4">
                            <dt class="text-sm text-gray-500">
                                Service
                            </dt>

                            <dd class="text-sm font-medium text-gray-900">
                                {{ $confirmationServiceName }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-sm text-gray-500">
                                Date
                            </dt>

                            <dd class="text-sm font-medium text-gray-900">
                                {{ $confirmationDate }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-sm text-gray-500">
                                Time
                            </dt>

                            <dd class="text-sm font-medium text-gray-900">
                                {{ $confirmationStartTime }}
                                –
                                {{ $confirmationEndTime }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <button type="button" wire:click="$set('bookingConfirmed', false)"
                    class="mt-8 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                    Book another appointment
                </button>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-10 text-center">
                <h1 class="text-3xl font-semibold text-gray-900">
                    {{ $businessUser->name }}
                </h1>

                <p class="mt-2 text-gray-600">
                    Book an appointment online.
                </p>
            </div>

            {{-- Service --}}
            <section class="mb-8">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    1. Choose a service
                </h2>

                <div class="space-y-4">
                    @foreach ($services as $service)
                        <button type="button" wire:click="selectService({{ $service->id }})"
                            class="block w-full rounded-xl border bg-white p-5 text-left shadow-sm transition
                            {{ $selectedServiceId === $service->id
                                ? 'border-indigo-600 ring-2 ring-indigo-100'
                                : 'border-gray-200 hover:border-gray-300' }}">
                            <div class="flex items-start justify-between gap-6">
                                <div>
                                    <h3 class="font-medium text-gray-900">
                                        {{ $service->name }}
                                    </h3>

                                    @if ($service->description)
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $service->description }}
                                        </p>
                                    @endif

                                    <div class="mt-3 flex gap-4 text-sm text-gray-500">
                                        <span>
                                            {{ $service->duration }} min
                                        </span>

                                        <span>
                                            ${{ number_format($service->price, 2) }}
                                        </span>
                                    </div>
                                </div>

                                <span class="text-sm font-medium text-indigo-600">
                                    {{ $selectedServiceId === $service->id ? 'Selected' : 'Select' }}
                                </span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </section>

            {{-- Date --}}
            @if ($selectedServiceId)
                <section id="booking-date-section" class="mb-8 scroll-mt-6">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">
                        2. Choose a date
                    </h2>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <label for="booking-date" class="block text-sm font-medium text-gray-700">
                            Date
                        </label>

                        <input id="booking-date" type="date" value="{{ $selectedDate }}"
                            min="{{ now()->toDateString() }}" wire:change="selectDate($event.target.value)"
                            class="mt-2 block w-full rounded-md border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs">
                    </div>
                </section>
            @endif

            {{-- Available slots --}}
            @if ($selectedDate)
                <section class="mb-8">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">
                        3. Choose a time
                    </h2>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        @if (count($availableSlots))
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach ($availableSlots as $slot)
                                    @php
                                        $slotStart = $slot['starts_at']->format('Y-m-d H:i:s');
                                        $isSelected = $selectedStartAt === $slotStart;
                                    @endphp

                                    <button type="button"
                                        wire:click="selectSlot('{{ $slot['starts_at']->format('H:i') }}')"
                                        class="rounded-md border px-4 py-3 text-sm font-medium transition
                                        {{ $isSelected
                                            ? 'border-indigo-600 bg-indigo-600 text-white'
                                            : 'border-gray-300 bg-white text-gray-700 hover:border-indigo-500 hover:text-indigo-600' }}">
                                        {{ $slot['starts_at']->format('H:i') }}
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-600">
                                There are no available times for this date.
                            </p>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Customer details --}}
            @if ($selectedStartAt)
                <section class="mb-8">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">
                        4. Your details
                    </h2>

                    <div class="space-y-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                        <div>
                            <label for="customer-name" class="block text-sm font-medium text-gray-700">
                                Name
                            </label>

                            <input id="customer-name" type="text" wire:model="customerName" autocomplete="name"
                                class="mt-2 block w-full rounded-md border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                            @error('customerName')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="customer-email" class="block text-sm font-medium text-gray-700">
                                Email
                            </label>

                            <input id="customer-email" type="email" wire:model="customerEmail" autocomplete="email"
                                class="mt-2 block w-full rounded-md border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                            @error('customerEmail')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="customer-phone" class="block text-sm font-medium text-gray-700">
                                Phone
                            </label>

                            <input id="customer-phone" type="tel" wire:model="customerPhone" autocomplete="tel"
                                class="mt-2 block w-full rounded-md border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                            @error('customerPhone')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="customer-notes" class="block text-sm font-medium text-gray-700">
                                Notes
                            </label>

                            <textarea id="customer-notes" wire:model="customerNotes" rows="4"
                                class="mt-2 block w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>

                            @error('customerNotes')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        @error('booking')
                            <p class="text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                        <button type="button" wire:click="book" wire:loading.attr="disabled" wire:target="book"
                            class="w-full rounded-md bg-indigo-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="book">
                                Book appointment
                            </span>

                            <span wire:loading wire:target="book">
                                Booking...
                            </span>
                        </button>
                    </div>
                </section>
            @endif

        </div>
    @endif
</div>
