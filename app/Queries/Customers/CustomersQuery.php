<?php

namespace App\Queries\Customers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CustomersQuery
{
    public function get(User $user): Collection
    {
        return $user->customers()
            ->orderBy('name')
            ->get();
    }
}
