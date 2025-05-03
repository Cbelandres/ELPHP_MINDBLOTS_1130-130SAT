<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FarmerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'No authenticated user found'], 401);
        }

        if ($user->role !== 'farmer') {
            return response()->json([
                'message' => 'Unauthorized. Farmer access required.',
                'user_role' => $user->role
            ], 403);
        }

        return $next($request);
    }
} 