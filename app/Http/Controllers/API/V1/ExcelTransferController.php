<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Excel\ImportExcelRequest;
use App\Services\ExcelTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelTransferController extends Controller
{
    public function __construct(private ExcelTransferService $excelTransferService) {}

    public function export(Request $request): BinaryFileResponse
    {
        $artifact = $this->excelTransferService->exportWorkbook($request->user());

        return response()
            ->download($artifact['path'], $artifact['filename'])
            ->deleteFileAfterSend(true);
    }

    public function template(Request $request): BinaryFileResponse
    {
        $artifact = $this->excelTransferService->exportTemplate($request->user());

        return response()
            ->download($artifact['path'], $artifact['filename'])
            ->deleteFileAfterSend(true);
    }

    public function import(ImportExcelRequest $request): JsonResponse
    {
        $summary = $this->excelTransferService->importWorkbook(
            $request->file('file'),
            $request->user(),
        );

        return ApiResponse::success($summary, 'Importation Excel réussie');
    }
}
