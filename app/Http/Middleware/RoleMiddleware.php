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
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next, $roles)
    {
        $rolesArray = explode('|', $roles);

        if (! $request->user()->hasAnyRole($rolesArray)) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'message' => 'You do not have permission to perform this action.'
            ], 403);
        }

        return $next($request);
    }
}
