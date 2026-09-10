<?php

namespace App\Http\Controllers\API\V1\Commerce;

use App\Helpers\ApiResponse;
use App\Http\Controllers\API\V1\Commerce\Concerns\ResolvesCommerce;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\CloseCashSessionRequest;
use App\Http\Requests\Commerce\OpenCashSessionRequest;
use App\Http\Requests\Commerce\StoreCashMovementRequest;
use App\Http\Resources\Commerce\CashSessionResource;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Services\Commerce\CashSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashSessionController extends Controller
{
    use ResolvesCommerce;

    public function __construct(private CashSessionService $cashSessionService) {}

    public function open(OpenCashSessionRequest $request): JsonResponse
    {
        $commerce = $this->commerce($request);
        $register = CashRegister::query()
            ->where('commerce_id', $commerce->id)
            ->findOrFail($request->validated('cash_register_id'));

        $session = $this->cashSessionService->open($register, $request->validated(), $request->user());

        return ApiResponse::success(new CashSessionResource($session), 'Session ouverte', 201);
    }

    public function close(CloseCashSessionRequest $request, CashSession $cashSession): JsonResponse
    {
        $this->cashSessionService->assertSessionBelongsToCommerce($this->commerce($request), $cashSession);

        $session = $this->cashSessionService->close($cashSession, $request->validated(), $request->user());

        return ApiResponse::success(new CashSessionResource($session), 'Session fermée');
    }

    public function current(Request $request): JsonResponse
    {
        $request->validate(['cash_register_id' => ['required', 'integer']]);

        $commerce = $this->commerce($request);
        $register = CashRegister::query()
            ->where('commerce_id', $commerce->id)
            ->findOrFail($request->input('cash_register_id'));

        $session = $this->cashSessionService->current($register);

        return ApiResponse::success($session ? new CashSessionResource($session) : null);
    }

    public function storeMovement(StoreCashMovementRequest $request): JsonResponse
    {
        $commerce = $this->commerce($request);
        $session = CashSession::query()->findOrFail($request->validated('cash_session_id'));
        $this->cashSessionService->assertSessionBelongsToCommerce($commerce, $session);

        $data = $request->validated();
        $movement = match ($data['type']) {
            'CASH_IN' => $this->cashSessionService->cashIn($session, $data, $request->user()),
            'CASH_OUT' => $this->cashSessionService->cashOut($session, $data, $request->user()),
            'EXPENSE' => $this->cashSessionService->expense($session, $data, $request->user()),
            'WITHDRAWAL' => $this->cashSessionService->withdrawal($session, $data, $request->user()),
        };

        return ApiResponse::success($movement, 'Mouvement enregistré', 201);
    }
}
