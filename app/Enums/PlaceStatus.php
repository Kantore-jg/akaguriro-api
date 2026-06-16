<?php

namespace App\Enums;

enum PlaceStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Maintenance = 'maintenance';
    case Reserved = 'reserved';
}