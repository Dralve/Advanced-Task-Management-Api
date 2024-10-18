<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        if (! $request->user()->can($permission)) {
            return response()->json([
                'status' => 'error',
                'data' => null,
                'message' => 'You do not have permission to perform this action.'
            ], 403);
        }

        return $next($request);
    }
}
