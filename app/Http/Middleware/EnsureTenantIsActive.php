<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user->tenant) {
            return ApiResponse::error('Tenant not found for this user.', 403);
        }

        if (!$user->tenant->isActive()) {
            return ApiResponse::error('Tenant is not active.', 403);
        }

        return $next($request);
    }
}