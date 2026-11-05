<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewAsReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('office.view_as')) {
            return $next($request);
        }

        if (! $request->isMethod('GET') && ! $request->routeIs('office.view-as.stop')) {
            abort(403, 'View as is read-only.');
        }

        return $next($request);
    }
}
