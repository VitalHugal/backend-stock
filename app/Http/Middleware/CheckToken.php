<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckToken
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->hasHeader('Authorization')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }
    }
}