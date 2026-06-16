<?php

namespace App\Policies;

use App\Models\PaymentReceipt;
use App\Models\User;

class PaymentReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_receipts');
    }

    public function approve(User $user, PaymentReceipt $receipt): bool
    {
        return $user->can('manage_receipts');
    }

    public function reject(User $user, PaymentReceipt $receipt): bool
    {
        return $user->can('manage_receipts');
    }
}