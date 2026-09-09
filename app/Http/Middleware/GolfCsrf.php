<?php

namespace App\Http\Middleware;

use App\Support\GolfAccess;
use Closure;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

class GolfCsrf extends ValidateCsrfToken
{
    public function handle($request, Closure $next)
    {
        // The separate Vite preview has no Laravel session. Never exempt a live request.
        if ($request->is('api/*') && GolfAccess::localPreview($request)) return $next($request);
        return parent::handle($request, $next);
    }
}
