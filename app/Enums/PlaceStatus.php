<?php

namespace App\Enums;

enum PlaceStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Maintenance = 'maintenance';
    case Reserved = 'reserved';

    public function toUiLabel(): string
    {
        return match ($this) {
            self::Available => 'libre',
            self::Occupied => 'occupée',
            self::Maintenance => 'maintenance',
            self::Reserved => 'réservée',
        };
    }

    public static function fromUiLabel(string $value): self
    {
        return match ($value) {
            'libre', 'available' => self::Available,
            'occupée', 'occupied' => self::Occupied,
            'maintenance' => self::Maintenance,
            'réservée', 'reserved' => self::Reserved,
            default => self::from($value),
        };
    }
}
