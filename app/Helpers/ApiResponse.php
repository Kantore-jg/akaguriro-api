<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Operation successful',
        int $status = 200,
        ?array $meta = null
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => self::transformData($data),
            'meta' => $meta ?? self::extractMeta($data),
            'errors' => null,
        ];

        return response()->json($response, $status);
    }

    public static function error(
        string $message = 'Operation failed',
        mixed $errors = null,
        int $status = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => null,
            'errors' => $errors,
        ], $status);
    }

    private static function transformData(mixed $data): mixed
    {
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return $data->resolve();
        }

        return $data;
    }

    private static function extractMeta(mixed $data): ?array
    {
        if ($data instanceof AbstractPaginator) {
            return [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ];
        }

        if ($data instanceof ResourceCollection && $data->resource instanceof AbstractPaginator) {
            $paginator = $data->resource;

            return [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ];
        }

        return null;
    }
}