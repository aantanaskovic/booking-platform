<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function occupiesSlot(): bool
    {
        return match ($this) {
            self::PENDING,
            self::CONFIRMED => true,

            self::CANCELLED,
            self::COMPLETED => false,
        };
    }

    public static function occupying(): array
    {
        return [
            self::PENDING,
            self::CONFIRMED,
        ];
    }

    public function canBeCancelled(): bool
    {
        return match ($this) {
            self::PENDING,
            self::CONFIRMED => true,

            self::CANCELLED,
            self::COMPLETED => false,
        };
    }
}
