<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\StoreCashRegisterRequest;
use App\Http\Resources\Commerce\CashRegisterResource;
use App\Models\CashRegister;
use App\Services\Commerce\CashSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CashRegisterController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private CashSessionService $cashSessionService) {}

    public function index(Request $request): JsonResponse
    {
        $registers = CashRegister::query()
            ->where('commerce_id', $this->commerce($request)->id)
            ->orderBy('name')
            ->get();

        return ApiResponse::success(CashRegisterResource::collection($registers));
    }

    public function store(StoreCashRegisterRequest $request): JsonResponse
    {
        $register = CashRegister::create([
            'commerce_id' => $this->commerce($request)->id,
            ...$request->validated(),
        ]);

        return ApiResponse::success(new CashRegisterResource($register), 'Caisse créée', 201);
    }

    public function show(Request $request, CashRegister $cashRegister): JsonResponse
    {
        $this->assertBelongs($request, $cashRegister);

        return ApiResponse::success(new CashRegisterResource($cashRegister));
    }

    public function update(StoreCashRegisterRequest $request, CashRegister $cashRegister): JsonResponse
    {
        $this->assertBelongs($request, $cashRegister);
        $cashRegister->update($request->validated());

        return ApiResponse::success(new CashRegisterResource($cashRegister->fresh()), 'Caisse mise à jour');
    }

    private function assertBelongs(Request $request, CashRegister $register): void
    {
        $this->cashSessionService->assertRegisterBelongsToCommerce($this->commerce($request), $register);
    }
}
