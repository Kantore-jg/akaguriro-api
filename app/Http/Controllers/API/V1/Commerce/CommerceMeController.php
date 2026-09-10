<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\UpdateCommerceRequest;
use App\Http\Resources\Commerce\CommerceResource;
use App\Services\Commerce\CommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommerceMeController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private CommerceService $commerceService) {}

    public function show(Request $request): JsonResponse
    {
        $commerce = $this->commerce($request);
        $commerce->loadCount('commerceUsers');

        return ApiResponse::success(new CommerceResource($commerce));
    }

    public function update(UpdateCommerceRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo');
        }

        $commerce = $this->commerceService->update($this->commerce($request), $data);

        return ApiResponse::success(new CommerceResource($commerce), 'Commerce mis à jour');
    }
}
