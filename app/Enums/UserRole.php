<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'SUPER_ADMIN';
    case AdminMarche = 'ADMIN_MARCHE';
    case ProprietaireMarche = 'PROPRIETAIRE_MARCHE';
    case Commercant = 'COMMERCANT';
    case CommerceUser = 'COMMERCE_USER';
    case User = 'USER';
}