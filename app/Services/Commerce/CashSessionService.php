<?php

namespace App\Services\Commerce;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Commerce;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashSessionService
{
    public function open(CashRegister $register, array $data, User $user): CashSession
    {
        if ($register->currentOpenSession()) {
            throw ValidationException::withMessages([
                'cash_register_id' => ['Une session est déjà ouverte pour cette caisse.'],
            ]);
        }

        return CashSession::create([
            'cash_register_id' => $register->id,
            'opened_by' => $user->id,
            'opening_float' => round((float) ($data['opening_float'] ?? 0), 2),
            'status' => 'open',
            'opened_at' => now(),
            'notes' => $data['notes'] ?? null,
        ])->load(['register', 'opener']);
    }

    public function close(CashSession $session, array $data, User $user): CashSession
    {
        if ($session->status !== 'open') {
            throw ValidationException::withMessages([
                'session' => ['Cette session est déjà fermée.'],
            ]);
        }

        $theoretical = $this->calculateTheoreticalBalance($session);
        $closingCounted = round((float) $data['closing_counted'], 2);

        $session->update([
            'closed_by' => $user->id,
            'closing_counted' => $closingCounted,
            'theoretical_balance' => $theoretical,
            'variance' => round($closingCounted - $theoretical, 2),
            'status' => 'closed',
            'closed_at' => now(),
            'notes' => $data['notes'] ?? $session->notes,
        ]);

        return $session->fresh(['register', 'opener', 'closer', 'movements']);
    }

    public function current(CashRegister $register): ?CashSession
    {
        return $register->currentOpenSession()?->load(['register', 'opener', 'movements']);
    }

    public function recordMovement(
        CashSession $session,
        string $type,
        float $amount,
        ?string $motif,
        User $user,
        ?object $reference = null
    ): CashMovement {
        if ($session->status !== 'open') {
            throw ValidationException::withMessages([
                'session' => ['La session de caisse n\'est pas ouverte.'],
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Le montant doit être positif.'],
            ]);
        }

        return CashMovement::create([
            'cash_session_id' => $session->id,
            'type' => $type,
            'amount' => round($amount, 2),
            'motif' => $motif,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->id,
            'user_id' => $user->id,
            'moved_at' => now(),
        ]);
    }

    public function cashIn(CashSession $session, array $data, User $user): CashMovement
    {
        return $this->recordMovement(
            $session,
            'CASH_IN',
            (float) $data['amount'],
            $data['motif'] ?? null,
            $user
        );
    }

    public function cashOut(CashSession $session, array $data, User $user): CashMovement
    {
        return $this->recordMovement(
            $session,
            'CASH_OUT',
            (float) $data['amount'],
            $data['motif'] ?? null,
            $user
        );
    }

    public function expense(CashSession $session, array $data, User $user): CashMovement
    {
        return $this->recordMovement(
            $session,
            'EXPENSE',
            (float) $data['amount'],
            $data['motif'] ?? null,
            $user
        );
    }

    public function withdrawal(CashSession $session, array $data, User $user): CashMovement
    {
        return $this->recordMovement(
            $session,
            'WITHDRAWAL',
            (float) $data['amount'],
            $data['motif'] ?? null,
            $user
        );
    }

    public function calculateTheoreticalBalance(CashSession $session): float
    {
        $balance = (float) $session->opening_float;

        foreach ($session->movements as $movement) {
            $amount = (float) $movement->amount;

            $balance += match ($movement->type) {
                'SALE', 'CASH_IN' => $amount,
                'CASH_OUT', 'EXPENSE', 'WITHDRAWAL' => -$amount,
                default => 0,
            };
        }

        return round($balance, 2);
    }

    public function assertRegisterBelongsToCommerce(Commerce $commerce, CashRegister $register): void
    {
        if ((int) $register->commerce_id !== (int) $commerce->id) {
            throw ValidationException::withMessages([
                'cash_register' => ['Caisse introuvable pour ce commerce.'],
            ]);
        }
    }

    public function assertSessionBelongsToCommerce(Commerce $commerce, CashSession $session): void
    {
        $session->loadMissing('register');

        if ((int) $session->register->commerce_id !== (int) $commerce->id) {
            throw ValidationException::withMessages([
                'session' => ['Session introuvable pour ce commerce.'],
            ]);
        }
    }
}
