<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!\Illuminate\Support\Facades\Auth::check() || !\Illuminate\Support\Facades\Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
