<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'SUPER_ADMIN';
    case AdminMarche = 'ADMIN_MARCHE';
    case Commercant = 'COMMERCANT';
    case User = 'USER';
}