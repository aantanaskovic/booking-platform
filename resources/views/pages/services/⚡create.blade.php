<?php

use App\Actions\Services\CreateService;
use Livewire\Component;

new class extends Component {
    public string $name = '';
    public string $description = '';
    public string $duration = '';
    public string $price = '';
    public bool $isActive = true;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'isActive' => ['boolean'],
        ];
    }

    public function save(CreateService $createService): void
    {
        $this->validate();

        $createService->create(
            user: auth()->user(),
            data: [
                'name' => $this->name,
                'description' => $this->description ?: null,
                'duration' => (int) $this->duration,
                'price' => $this->price,
                'is_active' => $this->isActive,
            ],
        );

        $this->redirectRoute('services.index');
    }
};
?>

<div class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                Add service
            </h1>

            <p class="mt-2 text-sm text-gray-600">
                Add a new service that your business offers.
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
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">
                        Description
                    </label>

                    <textarea id="description" wire:model="description" rows="4"
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"></textarea>

                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="duration" class="block text-sm font-medium text-gray-700">
                        Duration (minutes)
                    </label>

                    <input id="duration" type="number" min="1" wire:model="duration"
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">

                    @error('duration')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">
                        Price
                    </label>

                    <input id="price" type="number" min="0" step="0.01" wire:model="price"
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">

                    @error('price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-start gap-3">
                    <input id="isActive" type="checkbox" wire:model="isActive"
                        class="mt-1 h-4 w-4 rounded border-gray-300">

                    <div>
                        <label for="isActive" class="text-sm font-medium text-gray-700">
                            Active
                        </label>

                        <p class="text-sm text-gray-500">
                            Active services can be booked by customers.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('services.index') }}"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                        Cancel
                    </a>

                    <button type="submit"
                        class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        Add service
                    </button>
                </div>

            </form>

        </div>

    </div>
</div>
