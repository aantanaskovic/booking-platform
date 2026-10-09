<?php

namespace App\Queries\Services;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ServicesQuery
{
    public function get(User $user): Collection
    {
        return $user->services()
            ->orderBy('name')
            ->get();
    }
}
