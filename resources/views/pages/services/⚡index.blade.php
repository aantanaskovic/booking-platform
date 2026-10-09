<?php

use App\Queries\Services\ServicesQuery;
use Livewire\Component;

new class extends Component {
    public $services;

    public function mount(ServicesQuery $servicesQuery): void
    {
        $this->services = $servicesQuery->get(auth()->user());
    }
};
?>

<div class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                    Services
                </h1>

                <p class="mt-2 text-sm text-gray-600">
                    Manage the services your business offers.
                </p>
            </div>

            <a href="{{ route('services.create') }}"
                class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                Add service
            </a>
        </div>

        @if ($services->isNotEmpty())
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Name
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Duration
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Price
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Status
                            </th>

                            <th
                                class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($services as $service)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $service->name }}
                                    </div>

                                    @if ($service->description)
                                        <div class="mt-1 text-sm text-gray-500">
                                            {{ $service->description }}
                                        </div>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                    {{ $service->duration }} min
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                    {{ number_format($service->price, 2, ',', '.') }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    @if ($service->is_active)
                                        <span
                                            class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                                            Active
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <a href="{{ route('services.edit', ['serviceId' => $service->id]) }}"
                                        class="font-medium text-gray-700 hover:text-gray-900">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <h3 class="text-lg font-semibold text-gray-900">
                    No services yet.
                </h3>

                <p class="mt-2 text-sm text-gray-500">
                    Add your first service to get started.
                </p>
            </div>
        @endif

    </div>
</div>
