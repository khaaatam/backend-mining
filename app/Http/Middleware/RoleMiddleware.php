<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Cek apakah user sudah login dan punya role yang diminta
        if (!$request->user() || !$request->user()->hasRole($role)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses (Forbidden)',
            ], 403);
        }

        return $next($request);
    }
}
