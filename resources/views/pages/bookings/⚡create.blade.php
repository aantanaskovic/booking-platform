<?php

use App\Actions\Bookings\CreateBooking;
use App\Exceptions\Bookings\BookingException;
use App\Models\Customer;
use App\Models\Service;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    public ?int $customerId = null;

    public ?int $serviceId = null;

    #[Url]
    public ?string $date = null;

    #[Url]
    public ?string $returnRoute = null;

    public string $startsAt = '';

    public string $endsAt = '';

    public string $notes = '';

    public $customers;

    public $services;

    public function mount(): void
    {
        if ($this->date) {
            $this->startsAt = $this->date . 'T09:00';
        }

        $this->customers = auth()->user()->customers()->orderBy('name')->get();

        $this->services = auth()->user()->services()->where('is_active', true)->orderBy('name')->get();

        $this->updateEndsAt();
    }

    public function updatedServiceId(): void
    {
        $this->updateEndsAt();
    }

    public function updatedStartsAt(): void
    {
        $this->updateEndsAt();
    }

    private function updateEndsAt(): void
    {
        if (!$this->serviceId || !$this->startsAt) {
            $this->endsAt = '';

            return;
        }

        $service = $this->services->firstWhere('id', $this->serviceId);

        if (!$service) {
            $this->endsAt = '';

            return;
        }

        $this->endsAt = Carbon::parse($this->startsAt)->addMinutes($service->duration)->format('Y-m-d\TH:i');
    }

    protected function rules(): array
    {
        return [
            'customerId' => ['required', 'integer'],
            'serviceId' => ['required', 'integer'],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['required', 'date', 'after:startsAt'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function save(CreateBooking $createBooking): void
    {
        $this->validate();

        try {
            $createBooking->create(
                user: auth()->user(),
                customerId: $this->customerId,
                serviceId: $this->serviceId,
                data: [
                    'starts_at' => $this->startsAt,
                    'ends_at' => $this->endsAt,
                    'notes' => $this->notes ?: null,
                ],
            );
        } catch (BookingSlotUnavailableException) {
            $this->addError('booking', 'This time slot is no longer available. Please choose another time.');

            return;
        } catch (BookingException $exception) {
            $this->addError('booking', $exception->getMessage());

            return;
        }

        $this->redirectBack();
    }

    public function cancel(): void
    {
        $this->redirectBack();
    }

    private function redirectBack(): void
    {
        if ($this->returnRoute === 'bookings.calendar') {
            $this->redirectRoute('bookings.calendar');

            return;
        }

        if ($this->returnRoute === 'bookings.index') {
            $this->redirectRoute('bookings.index');

            return;
        }

        $this->redirectRoute('dashboard');
    }
};
?>

<div class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="mb-8">
            <a href="{{ route('bookings.index') }}"
                class="text-sm font-medium text-gray-600 transition hover:text-gray-900">
                ← Back to bookings
            </a>

            <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900">
                Create booking
            </h1>

            <p class="mt-2 text-sm text-gray-600">
                Create a new booking for one of your customers.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">

            @error('booking')
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $message }}
                </div>
            @enderror

            <form wire:submit="save" class="space-y-6">

                <div>
                    <label for="customerId" class="block text-sm font-medium text-gray-900">
                        Customer
                    </label>

                    <select id="customerId" wire:model="customerId"
                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900">
                        <option value="">Select customer</option>

                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('customerId')
                        <p class="mt-1.5 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="serviceId" class="block text-sm font-medium text-gray-900">
                        Service
                    </label>

                    <select id="serviceId" wire:model.live="serviceId"
                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900">
                        <option value="">Select service</option>

                        @foreach ($services as $service)
                            <option value="{{ $service->id }}">
                                {{ $service->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('serviceId')
                        <p class="mt-1.5 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="startsAt" class="block text-sm font-medium text-gray-900">
                            Starts at
                        </label>

                        <input id="startsAt" type="datetime-local" wire:model.live="startsAt"
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900">

                        @error('startsAt')
                            <p class="mt-1.5 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Ends at
                        </label>

                        @if ($endsAt)
                            <input type="datetime-local" value="{{ $endsAt }}" disabled
                                class="mt-2 block w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2.5 text-sm shadow-sm" />
                        @else
                            <div
                                class="mt-2 block w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2.5 text-sm text-gray-500 shadow-sm">
                                Select a service and start time
                            </div>
                        @endif
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-900">
                        Notes
                    </label>

                    <textarea id="notes" wire:model="notes" rows="4"
                        class="mt-2 block w-full resize-y rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                        placeholder="Add any notes about this booking..."></textarea>

                    @error('notes')
                        <p class="mt-1.5 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6">
                    <a href="{{ route('bookings.index') }}"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Cancel
                    </a>

                    <button type="submit"
                        class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800 data-loading:pointer-events-none data-loading:opacity-50">
                        <span class="in-data-loading:hidden">
                            Create booking
                        </span>

                        <span class="not-in-data-loading:hidden">
                            Creating...
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
