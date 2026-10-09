<?php

namespace App\Actions\Services;

use App\Models\Service;
use App\Models\User;

class UpdateService
{
    public function update(
        User $user,
        int $serviceId,
        array $data,
    ): Service {
        $service = $user->services()->findOrFail($serviceId);

        $service->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'duration' => $data['duration'],
            'price' => $data['price'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $service;
    }
}
