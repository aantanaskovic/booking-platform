<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use App\Models\User;

class FindOrCreateCustomer
{
    public function findOrCreate(User $user, array $data): Customer
    {
        $customer = $user->customers()
            ->where('email', $data['email'])
            ->first();

        if ($customer) {
            return $customer;
        }

        return $user->customers()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
