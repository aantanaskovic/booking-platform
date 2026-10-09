<?php

use App\Queries\Customers\CustomersQuery;
use Livewire\Component;

new class extends Component {
    public $customers;

    public function mount(CustomersQuery $customersQuery): void
    {
        $this->customers = $customersQuery->get(auth()->user());
    }
};
?>

<div class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                    Customers
                </h1>

                <p class="mt-2 text-sm text-gray-600">
                    Manage your customers and their contact information.
                </p>
            </div>

            <a href="{{ route('customers.create') }}"
                class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                Add customer
            </a>
        </div>

        @if ($customers->isNotEmpty())
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
                                Email
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Phone
                            </th>

                            <th
                                class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($customers as $customer)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $customer->name }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                    {{ $customer->email ?? '—' }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                    {{ $customer->phone ?? '—' }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <a href="{{ route('customers.edit', ['customerId' => $customer->id]) }}"
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
                    No customers yet.
                </h3>

                <p class="mt-2 text-sm text-gray-500">
                    Add your first customer to get started.
                </p>
            </div>
        @endif

    </div>
</div>
