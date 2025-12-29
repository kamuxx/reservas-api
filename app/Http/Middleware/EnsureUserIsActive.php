<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 401);
        }

        if (!$user->isActive()) {
            auth()->logout();
            //throw new UnauthorizedHttpException('Unauthorized', 'User is not active');
            return response()->json([
                'message' => 'User is not active',
            ], 401);
        }
        return $next($request);
    }
}
