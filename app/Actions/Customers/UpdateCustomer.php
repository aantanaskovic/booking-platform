<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use App\Models\User;

class UpdateCustomer
{
    public function update(
        User $user,
        int $customerId,
        array $data,
    ): Customer {
        $customer = $user->customers()->findOrFail($customerId);

        $customer->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $customer;
    }
}
