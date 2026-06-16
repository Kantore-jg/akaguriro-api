<?php

namespace App\Repositories\Contracts;

use App\Models\Market;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface MarketRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Market;

    public function findBySlug(string $slug): ?Market;

    public function getPopular(int $limit = 5): Collection;

    public function create(array $data): Market;

    public function update(Market $market, array $data): Market;

    public function delete(Market $market): bool;
}