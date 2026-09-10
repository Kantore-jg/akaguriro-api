<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Services\Commerce\CommerceAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCommerceAccess
{
    public function __construct(private CommerceAccessService $access) {}

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        if (! $user) {
            return ApiResponse::error('Non authentifié.', null, 401);
        }

        $commerceId = $request->header('X-Commerce-Id')
            ?? $request->route('commerce')?->id
            ?? $request->input('commerce_id');

        try {
            $commerce = $this->access->resolveCommerce(
                $user,
                $commerceId ? (int) $commerceId : null
            );
            $this->access->assertMember($user, $commerce);

            foreach ($permissions as $permission) {
                if ($permission !== '') {
                    $this->access->assertPermission($user, $commerce, $permission);
                }
            }

            $request->attributes->set('commerce', $commerce);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Accès commerce refusé.', $e->errors(), 403);
        }

        return $next($request);
    }
}
