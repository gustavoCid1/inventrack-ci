<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->esAdmin()) {
            abort(403, 'Solo el administrador puede realizar esta acción.');
        }

        return $next($request);
    }
}
