<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use App\Models\User;

class CreateCustomer
{
    public function create(
        User $user,
        array $data,
    ): Customer {
        return $user->customers()->create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
