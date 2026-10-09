<?php

use App\Actions\Customers\UpdateCustomer;
use App\Models\Customer;
use Livewire\Component;

new class extends Component {
    public Customer $customer;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $notes = '';

    public function mount(int $customerId): void
    {
        $this->customer = auth()->user()->customers()->findOrFail($customerId);

        $this->name = $this->customer->name;
        $this->email = $this->customer->email ?? '';
        $this->phone = $this->customer->phone ?? '';
        $this->notes = $this->customer->notes ?? '';
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function save(UpdateCustomer $updateCustomer): void
    {
        $this->validate();

        $updateCustomer->update(
            user: auth()->user(),
            customerId: $this->customer->id,
            data: [
                'name' => $this->name,
                'email' => $this->email ?: null,
                'phone' => $this->phone ?: null,
                'notes' => $this->notes ?: null,
            ],
        );

        $this->redirectRoute('customers.index');
    }
};
?>

<div class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                Edit customer
            </h1>

            <p class="mt-2 text-sm text-gray-600">
                Update customer information.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

            <form wire:submit="save" class="space-y-6">

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Name
                    </label>

                    <input id="name" type="text" wire:model="name"
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">

                    @error('name')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email
                    </label>

                    <input id="email" type="email" wire:model="email"
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">

                    @error('email')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">
                        Phone
                    </label>

                    <input id="phone" type="text" wire:model="phone"
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">

                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">
                        Notes
                    </label>

                    <textarea id="notes" wire:model="notes" rows="4"
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"></textarea>

                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('customers.index') }}"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                        Cancel
                    </a>

                    <button type="submit"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        Save changes
                    </button>
                </div>

            </form>

        </div>

    </div>
</div>
