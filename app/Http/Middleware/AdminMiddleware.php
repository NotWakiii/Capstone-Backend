<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' =>
                    'Access denied. Admin only.'
            ], 403);
        }

        return $next($request);
    }
}
