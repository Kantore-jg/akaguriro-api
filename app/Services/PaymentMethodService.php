<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PaymentMethodService
{
    public function list(array $filters = [], ?User $actor = null): Collection
    {
        $query = PaymentMethod::query()->with('market')->orderBy('name');

        if ($actor?->managed_market_id && ! $actor->can('manage_markets')) {
            $query->where('market_id', $actor->managed_market_id);
        } elseif (! empty($filters['market_id'])) {
            $query->where('market_id', $filters['market_id']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->get();
    }

    public function create(array $data, User $actor): PaymentMethod
    {
        $marketId = $this->resolveMarketId($data['market_id'] ?? null, $actor);

        return PaymentMethod::create([
            'market_id' => $marketId,
            'name' => $data['name'],
            'type' => $data['type'] ?? 'bank',
            'account_number' => $data['account_number'] ?? null,
            'account_name' => $data['account_name'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ])->load('market');
    }

    public function update(PaymentMethod $method, array $data, User $actor): PaymentMethod
    {
        $this->assertCanManage($actor, $method);

        $method->update([
            'name' => $data['name'] ?? $method->name,
            'type' => $data['type'] ?? $method->type,
            'account_number' => array_key_exists('account_number', $data)
                ? $data['account_number']
                : $method->account_number,
            'account_name' => array_key_exists('account_name', $data)
                ? $data['account_name']
                : $method->account_name,
            'is_active' => array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : $method->is_active,
        ]);

        return $method->fresh('market');
    }

    public function deactivate(PaymentMethod $method, User $actor): PaymentMethod
    {
        $this->assertCanManage($actor, $method);
        $method->update(['is_active' => false]);

        return $method->fresh('market');
    }

    private function resolveMarketId(?int $marketId, User $actor): int
    {
        if ($actor->managed_market_id && ! $actor->can('manage_markets')) {
            return (int) $actor->managed_market_id;
        }

        if (! $marketId) {
            throw ValidationException::withMessages([
                'market_id' => ['Un marché est requis.'],
            ]);
        }

        return $marketId;
    }

    private function assertCanManage(User $actor, PaymentMethod $method): void
    {
        if ($actor->can('manage_markets')) {
            return;
        }

        if ($actor->can('manage_receipts')
            && $actor->managed_market_id
            && (int) $actor->managed_market_id === (int) $method->market_id
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'payment_method' => ['Vous n\'êtes pas autorisé à gérer ce moyen de paiement.'],
        ]);
    }
}
