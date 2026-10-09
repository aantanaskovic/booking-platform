<?php

namespace App\Actions\Services;

use App\Models\Service;
use App\Models\User;

class CreateService
{
    public function create(
        User $user,
        array $data,
    ): Service {
        return $user->services()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'duration' => $data['duration'],
            'price' => $data['price'],
            'is_active' => $data['is_active'] ?? true,
        ]);
    }
}
