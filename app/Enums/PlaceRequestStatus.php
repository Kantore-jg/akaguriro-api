<?php

namespace App\Enums;

enum PlaceRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Assigned = 'assigned';
}