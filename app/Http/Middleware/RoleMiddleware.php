<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Check if user has any of the required roles
        if (!$request->user()->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'Unauthorized. Insufficient permissions.',
                'required_roles' => $roles,
                'your_role' => $request->user()->getPrimaryRole(),
            ], 403);
        }

        return $next($request);
    }
}
