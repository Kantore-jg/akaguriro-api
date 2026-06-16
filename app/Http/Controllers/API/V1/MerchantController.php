<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::role(UserRole::Commercant->value)
            ->with(['chiefPlaces.market'])
            ->withCount('products');

        if ($request->filled('market_id')) {
            $query->whereHas('chiefPlaces', fn ($q) => $q->where('market_id', $request->market_id));
        }

        $merchants = $query->orderBy('name')->paginate((int) $request->get('per_page', 50));

        return ApiResponse::success(MerchantResource::collection($merchants));
    }
}