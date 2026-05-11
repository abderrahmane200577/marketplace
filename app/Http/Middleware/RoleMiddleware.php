<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes:
     *   ->middleware('role:admin')
     *   ->middleware('role:admin,vendor')
     *
     * @param  string  $roles  Comma-separated list of allowed roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Your account is suspended.'], 403);
        }

        if (! empty($roles) && ! in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Forbidden. You do not have permission to access this resource.',
                'required_role' => implode(' or ', $roles),
                'your_role'     => $user->role,
            ], 403);
        }

        return $next($request);
    }
}
