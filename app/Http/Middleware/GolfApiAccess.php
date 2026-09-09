<?php

namespace App\Http\Middleware;

use App\Support\GolfAccess;
use Closure;
use Illuminate\Http\Request;

class GolfApiAccess
{
    public function handle(Request $request, Closure $next)
    {
        GolfAccess::authorizeApi($request);
        return $next($request)->header('Cache-Control', 'no-store');
    }
}
